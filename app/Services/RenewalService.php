<?php

namespace App\Services;

use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
use App\Support\MemberProfile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orchestrates a membership renewal: resolves the fee, runs the Stripe charge
 * sequence, and dispatches the WildApricot renewal job. Lifetime memberships
 * are not renewable and are rejected by resolveTypeSlug().
 */
class RenewalService
{
    /**
     * Maps a WildApricot membership-level NAME to a membership-type slug.
     * This is the inverse of WildApricotService::resolveLevelId()'s name map.
     */
    private const LEVEL_NAME_TO_SLUG = [
        'Family Membership (Primary and Spouse only)'              => 'family',
        'Individual'                                               => 'individual',
        'Flat Membership'                                          => 'flat',
        'Checkomatic Membership (Primary and Spouse only)'         => 'checkomatic_family',
        'Checkomatic'                                              => 'checkomatic_individual',
        'Lifetime'                                                 => 'lifetime_individual',
    ];

    private const LIFETIME_SLUGS = ['lifetime_family', 'lifetime_individual'];

    public function __construct(private StripeService $stripe) {}

    /**
     * Resolve the member's membership-type slug from their WA level name.
     * @throws RuntimeException when the level is a lifetime (non-renewable) type.
     */
    public function resolveTypeSlug(MemberProfile $profile): string
    {
        $slug = $this->resolveSlug($profile);

        if (in_array($slug, self::LIFETIME_SLUGS, true)) {
            throw new RuntimeException(
                'Lifetime membership (' . trim($profile->level) . ') is not renewable.'
            );
        }

        return $slug;
    }

    /** True when the member's WA level is a non-renewable lifetime plan. */
    public function isLifetimeLevel(MemberProfile $profile): bool
    {
        return in_array($this->resolveSlug($profile), self::LIFETIME_SLUGS, true);
    }

    /**
     * Map the member's WA membership-level name to a membership-type slug.
     * Uses the LEVEL_NAME_TO_SLUG table, with a substring-based fallback for
     * level names not in the table.
     */
    private function resolveSlug(MemberProfile $profile): string
    {
        $levelName = trim($profile->level);
        $slug = self::LEVEL_NAME_TO_SLUG[$levelName] ?? null;
        if ($slug !== null) {
            return $slug;
        }

        $lower = strtolower($levelName);
        return match (true) {
            str_contains($lower, 'lifetime')    => 'lifetime_individual',
            str_contains($lower, 'checkomatic') => str_contains($lower, 'family') ? 'checkomatic_family' : 'checkomatic_individual',
            str_contains($lower, 'flat')        => 'flat',
            str_contains($lower, 'family')      => 'family',
            default                             => 'individual',
        };
    }

    /**
     * Resolve the renewal fee for a membership type.
     *
     * - flat:        $20 * (1 primary + $familyCount).
     * - checkomatic: the member-entered monthly amount.
     * - else:        the flat fee from config/membership.php.
     *
     * @return array{cents:int,label:string}
     */
    public function resolveFee(string $type, int $familyCount, ?float $checkomaticAmount): array
    {
        if ($type === 'flat') {
            $perMember = (int) (config('membership.fees')['flat']['cents'] ?? 2000);
            $cents = (1 + max(0, $familyCount)) * $perMember;
            return ['cents' => $cents, 'label' => '$' . number_format($cents / 100, 2)];
        }

        if ($type === 'checkomatic_family' || $type === 'checkomatic_individual') {
            $amount = (float) ($checkomaticAmount ?? 0);
            $cents  = (int) round($amount * 100);
            return ['cents' => $cents, 'label' => '$' . number_format($amount, 2) . '/mo'];
        }

        $fees = config('membership.fees');
        $entry = $fees[$type] ?? ['cents' => 0, 'label' => '$0.00'];
        return ['cents' => (int) $entry['cents'], 'label' => (string) $entry['label']];
    }

    /**
     * Build the data the renewal modal needs.
     * For checkomatic the fee cents are 0 until the member enters an amount.
     *
     * @return array{type:string,isCheckomatic:bool,fee:array,newRenewalDate:string,familyCount:int}
     */
    public function buildSummary(MemberProfile $profile): array
    {
        $type          = $this->resolveTypeSlug($profile);
        $isCheckomatic = str_starts_with($type, 'checkomatic');
        $familyCount   = count($profile->family);
        $fee           = $this->resolveFee($type, $familyCount, $isCheckomatic ? null : 0.0);

        return [
            'type'           => $type,
            'isCheckomatic'  => $isCheckomatic,
            'fee'            => $fee,
            'newRenewalDate' => $this->newRenewalDate($type),
            'familyCount'    => $familyCount,
        ];
    }

    /** The renewal date a successful renewal will set: end of next calendar year. */
    public function newRenewalDate(string $type): string
    {
        if (str_starts_with($type, 'checkomatic')) {
            return now()->addMonth()->format('F d, Y');
        }
        return now()->addYear()->endOfYear()->format('F d, Y');
    }

    /**
     * The renewal date a successful renewal will set, in ISO 8601 — the format
     * WildApricot expects for the RenewalDue field. Must represent the same
     * calendar date as newRenewalDate().
     */
    public function newRenewalDateIso(string $type): string
    {
        if (str_starts_with($type, 'checkomatic')) {
            return now()->addMonth()->toIso8601String();
        }
        return now()->addYear()->endOfYear()->toIso8601String();
    }

    /**
     * Run the Stripe charge sequence for a renewal. On success, persists a
     * 'paid' Renewal and dispatches ProcessMembershipRenewal.
     *
     * @return array{
     *   success:bool, renewal_id?:int, requires_action?:bool,
     *   client_secret?:string, payment_intent_id?:string, message?:string
     * }
     */
    public function charge(int $contactId, MemberProfile $profile, string $paymentMethodId, ?float $checkomaticAmount): array
    {
        $type = $this->resolveTypeSlug($profile); // throws for lifetime

        // Checkomatic renewals require a positive member-entered monthly amount.
        // Without this guard a missing amount would resolve to a $0 charge.
        if (str_starts_with($type, 'checkomatic') && (float) ($checkomaticAmount ?? 0) <= 0) {
            return [
                'success' => false,
                'message' => 'Please enter your monthly contribution amount.',
            ];
        }

        $familyCount = count($profile->family);
        $fee         = $this->resolveFee($type, $familyCount, $checkomaticAmount);

        $renewal = Renewal::create([
            'contact_id'               => $contactId,
            'member_email'             => $profile->email,
            'membership_type'          => $type,
            'amount_cents'             => $fee['cents'],
            'currency'                 => 'usd',
            'status'                   => 'pending',
            'stripe_payment_method_id' => $paymentMethodId,
        ]);

        $description = ucwords(str_replace('_', ' ', $type)) . ' Membership Renewal — ISGH';

        try {
            // ── Customer ──────────────────────────────────────────────────
            $customer = $this->stripe->createCustomer([
                'name'  => trim($profile->fullName) ?: $profile->email,
                'email' => $profile->email,
                'phone' => $profile->phone ?: null,
            ]);
            $renewal->update(['stripe_customer_id' => $customer->id]);

            // ── Payment method ───────────────────────────────────────────
            $pm   = $this->stripe->addPaymentMethodToCustomer($paymentMethodId, $customer->id);
            $card = $pm->card ?? null;
            $renewal->update([
                'payment_method' => $pm->type ?? null,
                'card_brand'     => $card->brand ?? null,
                'card_last4'     => $card->last4 ?? null,
            ]);

            // ── Payment intent ───────────────────────────────────────────
            $intent = $this->stripe->createPaymentIntent([
                'amount_cents' => $fee['cents'],
                'currency'     => 'usd',
                'customer_id'  => $customer->id,
                'description'  => $description,
                'metadata'     => [
                    'contact_id'      => $contactId,
                    'renewal_id'      => $renewal->id,
                    'membership_type' => $type,
                ],
            ]);
            $renewal->update(['stripe_payment_intent_id' => $intent->id]);

            // ── Confirm / charge ─────────────────────────────────────────
            $confirmed = $this->stripe->processPayment($intent->id, $paymentMethodId);
            $succeeded = ($confirmed->status ?? null) === 'succeeded';

            if (! $succeeded) {
                if (($confirmed->status ?? null) === 'requires_action') {
                    return [
                        'success'           => true,
                        'requires_action'   => true,
                        'client_secret'     => $confirmed->client_secret ?? '',
                        'payment_intent_id' => $intent->id,
                        'renewal_id'        => $renewal->id,
                    ];
                }
                $renewal->update(['status' => 'failed', 'error_message' => 'Payment status: ' . ($confirmed->status ?? 'unknown')]);
                return ['success' => false, 'message' => 'Payment could not be completed. Please try another card.'];
            }

            $renewal->update([
                'status'           => 'paid',
                'stripe_charge_id' => $confirmed->latest_charge ?? null,
                'paid_at'          => now(),
            ]);

            ProcessMembershipRenewal::dispatch($renewal);

            return ['success' => true, 'renewal_id' => $renewal->id];
        } catch (\Throwable $e) {
            $errorFields = $this->stripeErrorFields($e);
            $renewal->update(['status' => 'failed'] + $errorFields);
            Log::error('RenewalService::charge failed', [
                'renewal_id'   => $renewal->id,
                'decline_code' => $errorFields['error_decline_code'],
                'error'        => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $this->declineMessage($errorFields['error_decline_code'])];
        }
    }

    /**
     * Finalize a renewal whose payment required 3DS authentication.
     * Re-checks the PaymentIntent and, if it succeeded, marks the EXISTING
     * renewal paid and dispatches the WildApricot job — no new charge.
     *
     * @return array{success:bool, renewal_id?:int, message?:string}
     */
    public function finalize(int $renewalId, string $paymentIntentId): array
    {
        $renewal = Renewal::find($renewalId);
        if (! $renewal) {
            return ['success' => false, 'message' => 'Renewal not found.'];
        }

        // Already finalized — idempotent no-op (job already dispatched/done).
        if ($renewal->processed || $renewal->status === 'paid') {
            return ['success' => true, 'renewal_id' => $renewal->id];
        }

        try {
            $intent = $this->stripe->getPaymentIntent($paymentIntentId);
        } catch (\Throwable $e) {
            $renewal->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('RenewalService::finalize intent retrieval failed', [
                'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Could not verify payment. Please try again.'];
        }

        if (($intent->status ?? null) !== 'succeeded') {
            return [
                'success' => false,
                'message' => 'Payment is not complete. Status: ' . ($intent->status ?? 'unknown'),
            ];
        }

        $renewal->update([
            'status'           => 'paid',
            'stripe_charge_id' => $intent->latest_charge ?? null,
            'paid_at'          => now(),
        ]);

        ProcessMembershipRenewal::dispatch($renewal);

        return ['success' => true, 'renewal_id' => $renewal->id];
    }

    /**
     * Extract structured Stripe error fields from any Throwable.
     * Mirrors MembershipController's signup-flow error handling.
     *
     * @return array{error_type:string,error_code:?string,error_decline_code:?string,error_message:string}
     */
    private function stripeErrorFields(\Throwable $e): array
    {
        if (! ($e instanceof \Stripe\Exception\ApiErrorException)) {
            return [
                'error_type'         => 'api_error',
                'error_code'         => null,
                'error_decline_code' => null,
                'error_message'      => $e->getMessage(),
            ];
        }

        $err = $e->getError();

        return [
            'error_type'         => $err->type ?? 'api_error',
            'error_code'         => $err->code ?? null,
            'error_decline_code' => $err->decline_code ?? null,
            'error_message'      => $e->getMessage(),
        ];
    }

    /**
     * Map a Stripe decline code to a member-facing message.
     */
    private function declineMessage(?string $declineCode): string
    {
        return match ($declineCode) {
            'insufficient_funds'      => 'Your card has insufficient funds.',
            'card_declined'           => 'Your card was declined. Please try a different card.',
            'expired_card'            => 'Your card has expired.',
            'incorrect_cvc'           => 'The card security code is incorrect.',
            'lost_card', 'stolen_card'=> 'This card cannot be used. Please contact your bank.',
            default                  => 'Payment failed. Please try a different card.',
        };
    }
}

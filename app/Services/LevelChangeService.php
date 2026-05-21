<?php

namespace App\Services;

use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use App\Support\MemberProfile;
use App\Support\MembershipFee;
use App\Support\MembershipTypes;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates a membership level change: lists the available target levels,
 * resolves the target-level fee, runs the Stripe charge, and dispatches the
 * WildApricot level-change job.
 */
class LevelChangeService
{
    /**
     * Fee overrides applied only to the level-change flow. The shared
     * MembershipFee::resolve() reads config/membership.php which is also used
     * by public signup and renewals; these keys let the portal charge a
     * different amount when a member switches levels without affecting the
     * other flows.
     */
    private const FEE_OVERRIDES_CENTS = [
        'individual' => 2000,
    ];

    public function __construct(
        private StripeService $stripe,
        private WildApricotService $wa,
    ) {}

    /**
     * Resolve the fee for a level-change target, applying any portal-scoped
     * overrides on top of the shared MembershipFee math.
     *
     * @return array{cents:int,label:string}
     */
    private function resolveFee(string $type, int $familyCount, ?float $checkomaticAmount): array
    {
        $fee = MembershipFee::resolve($type, $familyCount, $checkomaticAmount);
        if (isset(self::FEE_OVERRIDES_CENTS[$type])) {
            $cents = self::FEE_OVERRIDES_CENTS[$type];
            $fee = ['cents' => $cents, 'label' => '$' . number_format($cents / 100, 2)];
        }
        return $fee;
    }

    /**
     * The membership types the member may switch to — all types minus their
     * current one and minus 'flat' (Flat Membership is not offered as a
     * level-change target in the member portal). Each entry:
     * {type, label, fee, includesFamily, isCheckomatic}.
     * For checkomatic types the fee cents are 0 until the member enters an amount.
     */
    public function availableLevels(MemberProfile $profile): array
    {
        $currentSlug = MembershipTypes::slugFromLevelName($profile->level);

        $levels = [];
        foreach (MembershipTypes::allSlugs() as $slug) {
            // Skip the member's current level, and exclude Flat Membership —
            // it is not a selectable target in the change-level form.
            if ($slug === $currentSlug || $slug === 'flat') {
                continue;
            }
            $isCheckomatic = MembershipTypes::isCheckomatic($slug);
            $levels[] = [
                'type'           => $slug,
                'label'          => MembershipTypes::labelForSlug($slug),
                'fee'            => $this->resolveFee($slug, 0, $isCheckomatic ? null : 0.0),
                'includesFamily' => MembershipTypes::includesFamily($slug),
                'isCheckomatic'  => $isCheckomatic,
            ];
        }

        return $levels;
    }

    /**
     * Run the Stripe charge for a level change. On success persists a 'paid'
     * LevelChange and dispatches ProcessLevelChange.
     *
     * @param  array  $familyMembers  Submitted spouse/family member rows (empty for non-family targets).
     * @return array{success:bool, level_change_id?:int, requires_action?:bool, client_secret?:string, payment_intent_id?:string, message?:string}
     */
    public function charge(int $contactId, MemberProfile $profile, string $toType, array $familyMembers, string $paymentMethodId, ?float $checkomaticAmount): array
    {
        $fromType = MembershipTypes::slugFromLevelName($profile->level);

        if ($toType === $fromType) {
            return ['success' => false, 'message' => 'You are already on that membership level.'];
        }
        if (! in_array($toType, MembershipTypes::allSlugs(), true)) {
            return ['success' => false, 'message' => 'Unknown membership level.'];
        }
        // Flat Membership is not a selectable level-change target — reject it
        // server-side too, in case a crafted request bypasses the UI.
        if ($toType === 'flat') {
            return ['success' => false, 'message' => 'Flat Membership is not available as a level-change option.'];
        }
        if (MembershipTypes::isCheckomatic($toType) && (float) ($checkomaticAmount ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Please enter your monthly contribution amount.'];
        }

        $family = MembershipTypes::includesFamily($toType)
            ? array_values(array_filter($familyMembers, fn ($m) => ! empty($m['first_name'])))
            : [];

        // Validate every added family member against WildApricot — same checks
        // as member creation: email-exists, phone-exists, name+DOB duplicate.
        // On a hit, abort before any charge and tell the frontend which block
        // (index) and which field group is at fault so it can be highlighted.
        foreach ($family as $i => $m) {
            $dupe = fn (string $field) => [
                'success'   => false,
                'duplicate' => ['index' => $i, 'field' => $field],
            ];

            $email = trim((string) ($m['email'] ?? ''));
            if ($email !== '' && $this->wa->searchContact($email, '', '', '', '')) {
                return $dupe('email') + ['message' => 'Family member email ' . $email . ' is already registered as an ISGH member.'];
            }

            $phoneDigits = preg_replace('/\D/', '', (string) ($m['phone'] ?? ''));
            if ($phoneDigits !== '' && $this->wa->checkPhoneExists($phoneDigits)) {
                return $dupe('phone') + ['message' => 'Family member phone ' . ($m['phone'] ?? '') . ' is already registered as an ISGH member.'];
            }

            // Name + DOB combination duplicate.
            if (! empty($m['first_name']) && $this->wa->searchContact('', $m['first_name'], $m['last_name'] ?? '', '', $m['dob'] ?? '')) {
                return $dupe('name_dob') + ['message' => 'The family member information you entered (' . trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')) . ') matches an existing member. Please verify the details or contact ISGH support.'];
            }
        }

        $fee = $this->resolveFee($toType, count($family), $checkomaticAmount);

        $levelChange = LevelChange::create([
            'contact_id'               => $contactId,
            'member_email'             => $profile->email,
            'from_type'                => $fromType,
            'to_type'                  => $toType,
            'amount_cents'             => $fee['cents'],
            'currency'                 => 'usd',
            'status'                   => 'pending',
            'family_members'           => $family,
            'stripe_payment_method_id' => $paymentMethodId,
        ]);

        $description = MembershipTypes::labelForSlug($toType) . ' — Level Change — ISGH';

        try {
            $customer = $this->stripe->createCustomer([
                'name'  => trim($profile->fullName) ?: $profile->email,
                'email' => $profile->email,
                'phone' => $profile->phone ?: null,
            ]);
            $levelChange->update(['stripe_customer_id' => $customer->id]);

            $pm   = $this->stripe->addPaymentMethodToCustomer($paymentMethodId, $customer->id);
            $card = $pm->card ?? null;
            $levelChange->update([
                'payment_method' => $pm->type ?? null,
                'card_brand'     => $card->brand ?? null,
                'card_last4'     => $card->last4 ?? null,
            ]);

            $intent = $this->stripe->createPaymentIntent([
                'amount_cents' => $fee['cents'],
                'currency'     => 'usd',
                'customer_id'  => $customer->id,
                'description'  => $description,
                'metadata'     => [
                    'contact_id'      => $contactId,
                    'level_change_id' => $levelChange->id,
                    'to_type'         => $toType,
                ],
            ]);
            $levelChange->update(['stripe_payment_intent_id' => $intent->id]);

            $confirmed = $this->stripe->processPayment($intent->id, $paymentMethodId);
            $succeeded = ($confirmed->status ?? null) === 'succeeded';

            if (! $succeeded) {
                if (($confirmed->status ?? null) === 'requires_action') {
                    return [
                        'success'           => true,
                        'requires_action'   => true,
                        'client_secret'     => $confirmed->client_secret ?? '',
                        'payment_intent_id' => $intent->id,
                        'level_change_id'   => $levelChange->id,
                    ];
                }
                $levelChange->update(['status' => 'failed', 'error_message' => 'Payment status: ' . ($confirmed->status ?? 'unknown')]);
                return ['success' => false, 'message' => 'Payment could not be completed. Please try another card.'];
            }

            $levelChange->update([
                'status'           => 'paid',
                'stripe_charge_id' => $confirmed->latest_charge ?? null,
                'paid_at'          => now(),
            ]);

            // Dispatch the WA-side job. On a sync queue this runs inline and
            // can throw — but Stripe has already charged the card, so any
            // failure here is a WA bookkeeping issue, NOT a Stripe decline.
            // Swallow the throw and let the job's own retry/failure path
            // handle it; do not roll back the LevelChange's "paid" status
            // and do not surface a decline message to the user.
            try {
                ProcessLevelChange::dispatch($levelChange);
            } catch (\Throwable $jobError) {
                Log::error('LevelChangeService: WA processing failed after successful Stripe charge', [
                    'level_change_id' => $levelChange->id,
                    'error'           => $jobError->getMessage(),
                ]);
            }

            return ['success' => true, 'level_change_id' => $levelChange->id];
        } catch (\Throwable $e) {
            $errorFields = $this->stripeErrorFields($e);
            $levelChange->update(['status' => 'failed'] + $errorFields);
            Log::error('LevelChangeService::charge failed', [
                'level_change_id' => $levelChange->id,
                'decline_code'    => $errorFields['error_decline_code'],
                'error'           => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $this->declineMessage($errorFields['error_decline_code'])];
        }
    }

    /**
     * Finalize a level change whose payment required 3DS authentication.
     * Re-checks the PaymentIntent and, if it succeeded, marks the EXISTING
     * LevelChange paid and dispatches the job — no new charge.
     *
     * @return array{success:bool, level_change_id?:int, message?:string}
     */
    public function finalize(int $levelChangeId, string $paymentIntentId): array
    {
        $levelChange = LevelChange::find($levelChangeId);
        if (! $levelChange) {
            return ['success' => false, 'message' => 'Level change not found.'];
        }

        if ($levelChange->processed || $levelChange->status === 'paid') {
            return ['success' => true, 'level_change_id' => $levelChange->id];
        }

        try {
            $intent = $this->stripe->getPaymentIntent($paymentIntentId);
        } catch (\Throwable $e) {
            $levelChange->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('LevelChangeService::finalize intent retrieval failed', [
                'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Could not verify payment. Please try again.'];
        }

        if (($intent->status ?? null) !== 'succeeded') {
            return [
                'success' => false,
                'message' => 'Payment is not complete. Status: ' . ($intent->status ?? 'unknown'),
            ];
        }

        $levelChange->update([
            'status'           => 'paid',
            'stripe_charge_id' => $intent->latest_charge ?? null,
            'paid_at'          => now(),
        ]);

        // See charge() — Stripe has succeeded, so don't let a sync-queue WA
        // failure bubble back to the controller as a 500. The job's own retry
        // path is responsible for the WA bookkeeping.
        try {
            ProcessLevelChange::dispatch($levelChange);
        } catch (\Throwable $jobError) {
            Log::error('LevelChangeService::finalize: WA processing failed after successful Stripe charge', [
                'level_change_id' => $levelChange->id,
                'error'           => $jobError->getMessage(),
            ]);
        }

        return ['success' => true, 'level_change_id' => $levelChange->id];
    }

    /**
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

    private function declineMessage(?string $declineCode): string
    {
        return match ($declineCode) {
            'insufficient_funds'       => 'Your card has insufficient funds.',
            'card_declined'            => 'Your card was declined. Please try a different card.',
            'expired_card'             => 'Your card has expired.',
            'incorrect_cvc'            => 'The card security code is incorrect.',
            'lost_card', 'stolen_card' => 'This card cannot be used. Please contact your bank.',
            default                    => 'Payment failed. Please try a different card.',
        };
    }
}

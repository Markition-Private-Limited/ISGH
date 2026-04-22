<?php

namespace App\Jobs;

use App\Models\PendingRegistration;
use App\Services\WildApricotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWildApricotRegistration implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(
        public PendingRegistration $pending,
        public string $chargeId,
        public float $amountPaid,
    ) {}

    public function handle(WildApricotService $wa): void
    {
        $pending = $this->pending->fresh();

        if ($pending->processed) {
            Log::info('ProcessWildApricotRegistration: already processed, skipping', [
                'ref' => $pending->stripe_intent_id,
            ]);
            return;
        }

        $data = $pending->data;
        $type = $data['type'];

        $isFamilyType = in_array($type, ['family', 'checkomatic_family', 'lifetime_family']);
        $primaryRole = match (true) {
            $type === 'flat'  => 'Owner',
            $isFamilyType     => 'Head of Household',
            default           => 'Individual',
        };

        $pending->update(['stripe_paid' => true]);

        $fail = function (string $step, Throwable $e) use ($pending): never {
            $pending->update([
                'wa_step'      => $step,
                'wa_error'     => $e->getMessage(),
                'wa_error_at'  => now(),
                'retry_count'  => $pending->retry_count + 1,
            ]);
            Log::error("ProcessWildApricotRegistration failed at step [{$step}]", [
                'error' => $e->getMessage(),
                'ref'   => $pending->stripe_intent_id,
            ]);
            throw $e;
        };

        $contactId   = (int) ($pending->wa_contact_id ?? 0);
        $invoiceId   = (int) ($pending->wa_invoice_id ?? 0);
        $paymentDone = in_array($pending->wa_step, ['spouses', 'done']);
        $bundleId    = (int) ($data['wa_bundle_id'] ?? 0);
        $levelId     = (int) ($data['wa_level_id'] ?? 0);

        $termsAgreedAt = now()->toIso8601String();

        // ── Step 1: Create WA contact ─────────────────────────────────────────
        if (! $contactId) {
            $pending->update(['wa_step' => 'contact']);
            try {
                $contact   = $wa->createActiveMember(array_merge($data['primary'], [
                    'membership_type' => $type,
                    'role'            => $primaryRole,
                    'zone'            => $data['zone'] ?? '',
                    'terms_agreed_at' => $termsAgreedAt,
                ]));
                $contactId = (int) $contact['Id'];
                $bundleId  = (int) $wa->extractFieldValue($contact, 'BundleId');
                $levelId   = (int) ($contact['MembershipLevel']['Id'] ?? $wa->resolveLevelId($type));
                $data      = array_merge($data, ['wa_bundle_id' => $bundleId, 'wa_level_id' => $levelId]);
                $pending->update(['wa_contact_id' => $contactId, 'data' => $data]);
            } catch (Throwable $e) {
                $fail('contact', $e);
            }
        } else {
            Log::info('ProcessWildApricotRegistration: skipping contact step', [
                'contact_id' => $contactId, 'ref' => $pending->stripe_intent_id,
            ]);
        }

        // ── Step 2: Create WA invoice ─────────────────────────────────────────
        if (! $invoiceId) {
            $pending->update(['wa_step' => 'invoice']);
            try {
                $invoice   = $wa->createMembershipInvoice($contactId, $this->amountPaid, $type);
                $invoiceId = (int) $invoice['Id'];
                $pending->update([
                    'wa_invoice_id' => $invoiceId,
                    'invoice_data'  => $invoice,
                ]);

                try {
                    $wa->setInvoiceNumberOnContact($contactId, $invoiceId);
                } catch (Throwable $e) {
                    Log::warning('ProcessWildApricotRegistration: Invoice# field update failed', [
                        'contact_id' => $contactId,
                        'invoice_id' => $invoiceId,
                        'error'      => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('invoice', $e);
            }
        } else {
            Log::info('ProcessWildApricotRegistration: skipping invoice step', [
                'invoice_id' => $invoiceId, 'ref' => $pending->stripe_intent_id,
            ]);
        }

        // ── Step 3: Record payment ────────────────────────────────────────────
        if (! $paymentDone) {
            $pending->update(['wa_step' => 'payment']);
            try {
                $stripePaymentMethodId = $pending->stripe_payment_method_id ?? '';
                $paymentResponse = $wa->recordPayment($contactId, $invoiceId, $this->amountPaid, $this->chargeId, $stripePaymentMethodId);
                $pending->update(['payment_data' => $paymentResponse]);

                try {
                    $wa->setPaymentProcessedOnContact($contactId, $this->chargeId);
                } catch (Throwable $e) {
                    Log::warning('ProcessWildApricotRegistration: Payment Processed / Proof Of Payment field update failed', [
                        'contact_id' => $contactId,
                        'error'      => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('payment', $e);
            }
        } else {
            Log::info('ProcessWildApricotRegistration: skipping payment step', [
                'ref' => $pending->stripe_intent_id,
            ]);
        }

        // ── Step 4: Add dependents ────────────────────────────────────────────
        $pending->update(['wa_step' => 'spouses']);

        if ($isFamilyType) {
            foreach ($data['spouses'] ?? [] as $spouse) {
                if (empty($spouse['first_name'])) continue;
                try {
                    $wa->addRelatedContact($contactId, $bundleId, $levelId, array_merge($spouse, [
                        'membership_type' => $type,
                        'role'            => 'Spouse',
                        'zone'            => $data['zone'] ?? '',
                        'terms_agreed_at' => $termsAgreedAt,
                        'invoice_number'  => $invoiceId,
                    ]));
                } catch (Throwable $e) {
                    Log::error('ProcessWildApricotRegistration: spouse add failed', ['error' => $e->getMessage()]);
                }
            }
        }

        // if ($type === 'flat' || $type === 'checkomatic_family') {
            foreach ($data['flat_members'] ?? [] as $flatMember) {
                if (empty($flatMember['first_name'])) continue;
                try {
                    $wa->addRelatedContact($contactId, $bundleId, $levelId, [
                        'membership_type'   => $type,
                        'first_name'        => $flatMember['first_name'] ?? '',
                        'last_name'         => $flatMember['last_name'] ?? '',
                        'middle_name'       => $flatMember['middle_name'] ?? '',
                        'email'             => $flatMember['email'] ?? '',
                        'dob'              => $flatMember['dob'] ?? '',
                        'phone'             => $flatMember['phone'] ?? '',
                        'tx_dl'             => $flatMember['tx_dl'] ?? '',
                        'role'              => $flatMember['relation'] ?? 'Family Member',
                        'zone'              => $data['zone'] ?? '',
                        'street'            => $flatMember['street'] ?: ($data['primary']['street'] ?? ''),
                        'city'              => $flatMember['city'] ?: ($data['primary']['city'] ?? ''),
                        'state'             => $flatMember['state'] ?: ($data['primary']['state'] ?? ''),
                        'zip'               => $flatMember['zip'] ?: ($data['primary']['zip'] ?? ''),
                        'terms_agreed_at'   => $termsAgreedAt,
                        'member_identifier' => $contactId,
                        'invoice_number'    => $invoiceId,
                    ]);
                } catch (Throwable $e) {
                    Log::error('ProcessWildApricotRegistration: flat member add failed', ['error' => $e->getMessage()]);
                }
            }
        // }

        // ── Step 5: Mark complete ─────────────────────────────────────────────
        $pending->update([
            'processed'    => true,
            'wa_step'      => 'done',
            'wa_error'     => null,
            'wa_error_at'  => null,
            'wa_contact_id' => $contactId,
            'wa_invoice_id' => $invoiceId,
            'processed_at' => now(),
        ]);

        Log::info('ProcessWildApricotRegistration: completed', [
            'contact_id' => $contactId,
            'invoice_id' => $invoiceId,
            'type'       => $type,
            'charge_id'  => $this->chargeId,
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessWildApricotRegistration: all retries exhausted', [
            'ref'   => $this->pending->stripe_intent_id,
            'error' => $e->getMessage(),
        ]);
    }
}

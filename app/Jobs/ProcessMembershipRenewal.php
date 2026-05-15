<?php

namespace App\Jobs;

use App\Models\Renewal;
use App\Services\MemberPortalService;
use App\Services\RenewalService;
use App\Services\WildApricotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes the WildApricot side of a membership renewal after Stripe has
 * confirmed payment: creates the renewal invoice, records the payment, and
 * advances RenewalDue for the primary member and every family member.
 *
 * Idempotent — re-running resumes from the first incomplete step, so a queue
 * retry never double-invoices or double-records a payment.
 */
class ProcessMembershipRenewal implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Renewal $renewal) {}

    public function handle(WildApricotService $wa): void
    {
        $renewal = $this->renewal->fresh();

        if ($renewal->processed) {
            Log::info('ProcessMembershipRenewal: already processed, skipping', ['renewal_id' => $renewal->id]);
            return;
        }

        $contactId = (int) $renewal->contact_id;
        $type      = (string) $renewal->membership_type;
        $amount    = $renewal->amount_cents / 100;

        // The renewal date the renewal advances every member to (ISO 8601).
        $renewalDue = app(RenewalService::class)->newRenewalDateIso($type);

        $fail = function (string $step, Throwable $e) use ($renewal): never {
            $renewal->update([
                'wa_step'       => $step,
                'error_message' => $e->getMessage(),
                'retry_count'   => $renewal->retry_count + 1,
            ]);
            Log::error("ProcessMembershipRenewal failed at step [{$step}]", [
                'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
            ]);
            throw $e;
        };

        // ── Step 1: Create the renewal invoice ────────────────────────────
        $invoiceId = (int) ($renewal->wa_invoice_id ?? 0);
        if (! $invoiceId) {
            $renewal->update(['wa_step' => 'invoice']);
            try {
                $invoice   = $wa->createMembershipInvoice($contactId, $amount, $type);
                $invoiceId = (int) $invoice['Id'];
                $renewal->update(['wa_invoice_id' => $invoiceId]);
                try {
                    $wa->setInvoiceNumberOnContact($contactId, $invoiceId);
                } catch (Throwable $e) {
                    Log::warning('ProcessMembershipRenewal: Invoice# field update failed', [
                        'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('invoice', $e);
            }
        }

        // ── Step 2: Record the payment ────────────────────────────────────
        if (! in_array($renewal->wa_step, ['payment', 'renewal', 'family', 'done'], true)) {
            $renewal->update(['wa_step' => 'payment']);
            try {
                $wa->recordPayment(
                    $contactId,
                    $invoiceId,
                    $amount,
                    (string) ($renewal->stripe_charge_id ?? ''),
                    (string) ($renewal->stripe_payment_method_id ?? '')
                );
                try {
                    $wa->setPaymentProcessedOnContact($contactId, (string) ($renewal->stripe_charge_id ?? ''));
                } catch (Throwable $e) {
                    Log::warning('ProcessMembershipRenewal: Payment Processed field update failed', [
                        'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('payment', $e);
            }
        }

        // ── Step 3: Advance the primary member's RenewalDue ───────────────
        $renewal->update(['wa_step' => 'renewal']);
        try {
            $wa->updateMember($contactId, [
                'membership_type' => $type,
                'renewal_due'     => $renewalDue,
            ]);
        } catch (Throwable $e) {
            $fail('renewal', $e);
        }

        // ── Step 4: Advance each family member's RenewalDue ───────────────
        $renewal->update(['wa_step' => 'family']);
        try {
            foreach ($wa->getFamilyMembers($contactId) as $family) {
                $familyId = (int) ($family['Id'] ?? 0);
                if ($familyId <= 0) {
                    continue;
                }
                try {
                    $wa->updateMember($familyId, [
                        'membership_type' => $type,
                        'renewal_due'     => $renewalDue,
                    ]);
                } catch (Throwable $e) {
                    // Non-fatal — one bad family record must not fail the renewal.
                    Log::warning('ProcessMembershipRenewal: family member update failed', [
                        'renewal_id' => $renewal->id, 'family_id' => $familyId, 'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            $fail('family', $e);
        }

        // ── Done ──────────────────────────────────────────────────────────
        $renewal->update(['wa_step' => 'done', 'processed' => true, 'status' => 'processed']);

        // Invalidate the cached member bundle so the dashboard reflects the renewal.
        try {
            app(MemberPortalService::class)->invalidate($contactId);
        } catch (Throwable $e) {
            Log::warning('ProcessMembershipRenewal: cache invalidation failed', [
                'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\LevelChange;
use App\Services\MemberPortalService;
use App\Services\WildApricotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes the WildApricot side of a membership level change after Stripe has
 * confirmed payment: creates the invoice, records the payment, switches the
 * membership level, and creates each newly-submitted family member as a
 * related WA contact.
 *
 * Idempotent — re-running resumes from the first incomplete step; family
 * members already created (tracked by index in created_family_ids) are skipped.
 */
class ProcessLevelChange implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public LevelChange $levelChange) {}

    public function handle(WildApricotService $wa): void
    {
        $levelChange = $this->levelChange->fresh();

        if ($levelChange->processed) {
            Log::info('ProcessLevelChange: already processed, skipping', ['level_change_id' => $levelChange->id]);
            return;
        }

        $contactId = (int) $levelChange->contact_id;
        $toType    = (string) $levelChange->to_type;
        $amount    = $levelChange->amount_cents / 100;

        $fail = function (string $step, Throwable $e) use ($levelChange): never {
            $levelChange->update([
                'wa_step'       => $step,
                'error_message' => $e->getMessage(),
                'retry_count'   => $levelChange->retry_count + 1,
            ]);
            Log::error("ProcessLevelChange failed at step [{$step}]", [
                'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
            ]);
            throw $e;
        };

        // ── Step 1: Invoice ───────────────────────────────────────────────
        $invoiceId = (int) ($levelChange->wa_invoice_id ?? 0);
        if (! $invoiceId) {
            $levelChange->update(['wa_step' => 'invoice']);
            try {
                $invoice   = $wa->createMembershipInvoice($contactId, $amount, $toType);
                $invoiceId = (int) $invoice['Id'];
                $levelChange->update(['wa_invoice_id' => $invoiceId]);
                try {
                    $wa->setInvoiceNumberOnContact($contactId, $invoiceId);
                } catch (Throwable $e) {
                    Log::warning('ProcessLevelChange: Invoice# field update failed', [
                        'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('invoice', $e);
            }
        }

        // ── Step 2: Record payment ────────────────────────────────────────
        if (! in_array($levelChange->wa_step, ['payment', 'level', 'family', 'done'], true)) {
            $levelChange->update(['wa_step' => 'payment']);
            try {
                $wa->recordPayment(
                    $contactId,
                    $invoiceId,
                    $amount,
                    (string) ($levelChange->stripe_charge_id ?? ''),
                    (string) ($levelChange->stripe_payment_method_id ?? '')
                );
                try {
                    $wa->setPaymentProcessedOnContact($contactId, (string) ($levelChange->stripe_charge_id ?? ''));
                } catch (Throwable $e) {
                    Log::warning('ProcessLevelChange: Payment Processed field update failed', [
                        'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('payment', $e);
            }
        }

        // ── Step 3: Switch the membership level ───────────────────────────
        $levelChange->update(['wa_step' => 'level']);
        try {
            $updated  = $wa->updateMember($contactId, ['membership_type' => $toType]);
            $bundleId = (int) $wa->extractFieldValue($updated, 'BundleId');
            $levelId  = (int) ($updated['MembershipLevel']['Id'] ?? 0);
            if ($bundleId === 0 || $levelId === 0) {
                $fresh    = $wa->getContactById($contactId) ?? [];
                $bundleId = $bundleId ?: (int) $wa->extractFieldValue($fresh, 'BundleId');
                $levelId  = $levelId ?: (int) ($fresh['MembershipLevel']['Id'] ?? 0);
            }
            $levelChange->update(['wa_bundle_id' => $bundleId, 'wa_level_id' => $levelId]);
        } catch (Throwable $e) {
            $fail('level', $e);
        }

        // ── Step 4: Create newly-added family members ─────────────────────
        $levelChange->update(['wa_step' => 'family']);
        try {
            $family     = $levelChange->family_members ?? [];
            $createdIds = $levelChange->created_family_ids ?? [];
            $bundleId   = (int) $levelChange->wa_bundle_id;
            $levelId    = (int) $levelChange->wa_level_id;

            foreach ($family as $idx => $member) {
                if (empty($member['first_name'])) {
                    continue;
                }
                if (array_key_exists($idx, $createdIds) || array_key_exists((string) $idx, $createdIds)) {
                    continue; // already created on a prior run
                }
                try {
                    $related = $wa->addRelatedContact($contactId, $bundleId, $levelId, array_merge($member, [
                        'membership_type' => $toType,
                    ]));
                    $createdIds[$idx] = (int) ($related['Id'] ?? 0);
                    $levelChange->update(['created_family_ids' => $createdIds]);
                } catch (Throwable $e) {
                    Log::warning('ProcessLevelChange: family member create failed', [
                        'level_change_id' => $levelChange->id, 'index' => $idx, 'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            $fail('family', $e);
        }

        // ── Done ──────────────────────────────────────────────────────────
        $levelChange->update(['wa_step' => 'done', 'processed' => true, 'status' => 'processed']);

        try {
            app(MemberPortalService::class)->invalidate($contactId);
        } catch (Throwable $e) {
            Log::warning('ProcessLevelChange: cache invalidation failed', [
                'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
            ]);
        }
    }

    /** Called when all retry attempts are exhausted. */
    public function failed(Throwable $e): void
    {
        $this->levelChange->fresh()?->update([
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
        ]);
        Log::error('ProcessLevelChange: permanently failed after retries', [
            'level_change_id' => $this->levelChange->id, 'error' => $e->getMessage(),
        ]);
    }
}

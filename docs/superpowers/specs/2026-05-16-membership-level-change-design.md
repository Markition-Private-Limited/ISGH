# Membership Level Change — Design

**Date:** 2026-05-16
**Status:** Approved design — ready for implementation planning

## Problem

The member portal dashboard has a "Change Level" item in the Quick Links card
that does nothing. A member should be able to change their membership level —
pick a new plan, add spouse/family members if the new plan includes them, pay
the new plan's fee via the existing Stripe flow, and have WildApricot updated:
the membership level switched, an invoice created, the payment recorded, and
any newly-added family members created as related WA contacts.

## Goals

- Wire the "Change Level" Quick Links item to a multi-step modal.
- Let the member pick any of the 7 membership types except their current one.
- When the target type includes a spouse/family, collect those members in the
  modal (signup-style dynamic add/remove blocks).
- Charge the full fee of the new level via the same Stripe flow signup/renewal use.
- Update WildApricot: switch the level + renewal date, create an invoice, record
  the payment, and create each newly-added family member as a related contact.
- Mirror the renewal feature's architecture (service + queued job + model).

## Non-Goals

- No proration — the member pays the full fee of the new level.
- No redesign of the dashboard beyond adding the modal.
- This feature does not modify or remove existing family members; it only
  creates the newly-submitted ones.

## Decisions (from brainstorming)

| Topic | Decision |
|---|---|
| Level options | All 7 membership types **except** the member's current one. |
| Payment amount | The **full fee** of the new level (from `config('membership.fees')`; flat = $20×members, checkomatic = member-entered monthly, standard = flat fee). |
| Family on upgrade | When the target type includes spouse/family, the form collects them and the job **creates each as a related WA contact** (`addRelatedContact`). |
| Code structure | A **new parallel** `LevelChangeService` + `ProcessLevelChange` job + `LevelChange` model — mirroring the renewal feature. |
| Shared logic | The fee resolver and the level-name↔slug map are extracted into shared `App\Support\MembershipFee` + `App\Support\MembershipTypes`; `RenewalService` is refactored to delegate to them (its outputs and tests unchanged). |

## Architecture

### New files

- **`app/Support/MembershipTypes.php`** — shared membership-type knowledge: the
  level-name↔slug map (currently private in `RenewalService`), plus helpers
  `slugFromLevelName(string): string`, `labelForSlug(string): string`,
  `includesFamily(string $slug): bool` (true for `family`, `checkomatic_family`,
  `lifetime_family`, `flat`), `isCheckomatic(string $slug): bool`.
- **`app/Support/MembershipFee.php`** — `resolve(string $type, int $familyCount,
  ?float $checkomaticAmount): array` returning `{cents, label}`. The 3-branch fee
  logic extracted verbatim from `RenewalService::resolveFee()`.
- **`app/Services/LevelChangeService.php`** — orchestrator:
  - `availableLevels(MemberProfile $profile): array` — the 7 types minus the
    member's current one, each `{type, label, fee, includesFamily, isCheckomatic}`.
  - `resolveFee(string $targetType, int $familyCount, ?float $checkomaticAmount): array`
    — delegates to `MembershipFee::resolve()`.
  - `charge(int $contactId, MemberProfile $profile, string $toType, array $familyMembers, string $paymentMethodId, ?float $checkomaticAmount): array`
    — guards, creates the `LevelChange` row, runs the Stripe sequence, dispatches
    `ProcessLevelChange` on success.
  - `finalize(int $levelChangeId, string $paymentIntentId): array` — idempotent
    3DS resume.
  - private `stripeErrorFields()` / `declineMessage()` — decline-code capture.
- **`app/Jobs/ProcessLevelChange.php`** — queued job (`tries=3`, `backoff=60`)
  for the WildApricot side.
- **`app/Models/LevelChange.php`** + **`database/migrations/xxxx_create_level_changes_table.php`**.

### Modified files

- **`app/Services/RenewalService.php`** — `resolveFee()` delegates to
  `MembershipFee::resolve()`; the level-name↔slug map delegates to
  `MembershipTypes`. No behavior change — its tests stay green.
- **`app/Http/Controllers/MemberPortalController.php`** — 4 thin endpoints:
  `changeLevelOptions`, `processLevelChange`, `finalizeLevelChange`,
  `levelChangeStatus`. Behind `member.portal.auth`.
- **`routes/web.php`** — 4 routes in the `member-portal` group.
- **`resources/views/member-portal/dashboard.blade.php`** — the "Change Level"
  Quick Links item opens the multi-step modal. The family-add blocks may be a
  Blade partial (`partials/level-change-family.blade.php`) to keep the dashboard
  file focused.

### Reused as-is

`config/membership.php`, `StripeService`, `WildApricotService::updateMember()` /
`addRelatedContact()` / `createMembershipInvoice()` / `recordPayment()` /
`setInvoiceNumberOnContact()`, `MemberPortalService::getBundle()`/`invalidate()`,
and the renewal modal's Stripe.js wiring pattern.

## Data Flow

### Opening the modal — `GET /member-portal/change-level/options`
1. Controller loads the member's `MemberProfile` from the cached bundle.
2. `LevelChangeService::availableLevels()` returns the 7 types minus the current
   one, each `{type, label, fee:{cents,label}, includesFamily:bool, isCheckomatic:bool}`.
3. Modal Step 1 renders the type cards.

### Selecting a type
- Client-side: if the chosen type's `includesFamily` is true, the modal shows
  Step 2 (signup-style dynamic spouse/flat-member blocks); otherwise it skips to
  Step 3. Checkomatic types show the monthly-amount input on Step 3.

### Submitting payment — `POST /member-portal/change-level`
1. Body: `target_type`, `family_members[]` (array of member objects, empty for
   non-family types), Stripe `payment_method_id`, optional `monthly_amount`.
2. Controller validates, derives `contactId` from session, guards: target type
   must differ from the current type (lifetime targets ARE allowed). Calls
   `LevelChangeService::charge()`.
3. `charge()` resolves the fee server-side, creates a `LevelChange` row
   (`status=pending`, stores `from_type`, `to_type`, the `family_members` JSON),
   runs the Stripe sequence (customer → PM → intent → `processPayment`). On
   `succeeded` → row `paid`, dispatch `ProcessLevelChange`; on `requires_action`
   → return `{requires_action, client_secret, payment_intent_id, level_change_id}`;
   on failure → row `failed` + decline message.

### WA processing — `ProcessLevelChange` job (queued)
1. Idempotency: `if ($levelChange->processed) return;`
2. `wa_step='invoice'` → `createMembershipInvoice(contactId, amount, toType)` →
   store `wa_invoice_id` → `setInvoiceNumberOnContact`.
3. `wa_step='payment'` → `recordPayment(...)`.
4. `wa_step='level'` → `updateMember(contactId, ['membership_type'=>$toType])` —
   switches the WA level + renewal date. Capture the new `BundleId` and `levelId`
   from the response; persist to `wa_bundle_id` / `wa_level_id`.
5. `wa_step='family'` → for each `family_members[]` entry with a non-empty
   `first_name`, `addRelatedContact($contactId, $wa_bundle_id, $wa_level_id,
   $entry)`. Per-member failures are logged, non-fatal. Successfully-created
   member indexes are recorded in a `created_family_ids` JSON column so a job
   retry skips them (no duplicate contacts). Skipped entirely when
   `family_members` is empty.
6. `wa_step='done'`, `processed=true`, `status='processed'`; invalidate the
   cached member bundle.
Each failed step records `wa_step`/error/retry_count and rethrows so the queue
retries; idempotency guards make a retry resume.

### 3DS finalize — `POST /member-portal/change-level/finalize`
Mirrors the renewal feature: re-verify the existing PaymentIntent via
`getPaymentIntent`; if `succeeded`, mark the existing `LevelChange` row `paid`
and dispatch the job. No second charge. Idempotent — an already-`paid`/`processed`
row is a no-op success.

### Success screen — `GET /member-portal/change-level/status/{levelChange}`
Returns the row state. `processed` → "Level changed!" + invoice number;
`failed` → support message; ownership-guarded by `contact_id`.

## LevelChange Model / `level_changes` Table

Columns: `id`, `contact_id`, `member_email`, `from_type`, `to_type`,
`amount_cents`, `currency`, `family_members` (JSON), `created_family_ids` (JSON),
`stripe_customer_id`, `stripe_payment_method_id`, `stripe_payment_intent_id`,
`stripe_charge_id`, `status` (`pending`/`paid`/`failed`/`processed`),
`wa_invoice_id`, `wa_bundle_id`, `wa_level_id`, `wa_step`
(`invoice`/`payment`/`level`/`family`/`done`), `processed` (bool), `retry_count`,
`error_type`, `error_code`, `error_decline_code`, `error_message`,
`payment_method`, `card_brand`, `card_last4`, `paid_at`, timestamps.
`family_members` and `created_family_ids` cast to `array`; `processed` to `bool`.
Explicit `$fillable` whitelist.

## Error Handling

- **Stripe failures** — single try/catch in `charge()`; failed charge sets
  `status=failed` + `stripeErrorFields()` decline data, returns the friendly
  `declineMessage()`. The WA job is dispatched only after Stripe `succeeded`.
- **WA job failures (post-charge)** — each step wrapped; failure records
  `wa_step`/error, increments retry count, rethrows → queue retries (`tries=3`).
  Idempotency guards resume the job. Exhausted retries → `failed()` hook sets
  `status=failed`.
- **Family-member creation** — each `addRelatedContact` in its own try/catch,
  non-fatal; created indexes tracked in `created_family_ids` so a retry never
  creates duplicates.
- **Guards** — target type == current → 422 (also filtered client-side);
  checkomatic target with no/zero `monthly_amount` → rejected before any
  Stripe/DB work; non-family target with a family array → family step skipped;
  no `contact_id` → 401; `levelChangeStatus` ownership-guarded; Pay button
  disabled during the in-flight request; `LevelChange` row + idempotent job
  prevent duplicate processing.
- Cached bundle invalidated on successful processing.

## Testing

- **`MembershipFee` / `MembershipTypes` unit tests** — fee resolution per branch,
  level-name→slug map, `includesFamily`/`isCheckomatic`. `RenewalService`'s
  existing tests act as the regression net for the delegation refactor.
- **`LevelChangeService` tests** — `Http::fake()` WA + mocked `StripeService`
  (real `Stripe\*` objects via `constructFrom`): `availableLevels` excludes the
  current type and flags `includesFamily`; `resolveFee` per branch; `charge()`
  → `paid` row + job on success, `failed` + decline code on decline,
  `requires_action` passthrough; `finalize()` idempotency; same-type and
  checkomatic-no-amount guards.
- **`ProcessLevelChange` job tests** — `Http::fake()` WA: creates invoice,
  records payment, switches level (`updateMember` PUT asserted), creates each
  family member (`addRelatedContact` POST asserted), captures `BundleId`, sets
  `processed`; idempotency (re-run skips completed steps + already-created
  family members); family-member failure non-fatal; non-family target skips
  the family step.
- **Controller feature tests** — `changeLevelOptions` options; `processLevelChange`
  with faked Stripe → `{success, level_change_id}` + row + job; same-type → 422;
  lifetime target allowed; `finalizeLevelChange` 3DS; `levelChangeStatus` state
  + cross-member 404.
- **Stripe** — faked, no real charges.
- **Manual smoke** — one level change end-to-end including an upgrade to a
  family plan with an added spouse, against live WA + Stripe test mode.

## Open Items for Implementation

- Confirm the real WA `MembershipLevel.Name` strings during the smoke test so
  `MembershipTypes`' level-name↔slug map is accurate.
- Confirm `updateMember`'s response carries the new `BundleId` after a level
  switch into a bundle plan; if not, the job does a fresh `getContactById` to
  read it before the family step.

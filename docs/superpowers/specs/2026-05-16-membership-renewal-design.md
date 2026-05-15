# Membership Renewal — Design

**Date:** 2026-05-16
**Status:** Approved design — ready for implementation planning

## Problem

The member portal shows expired members a "Renew Membership" button (and an
expired-state modal), but clicking it does nothing. Members need a working
renewal flow: pay the membership fee via Stripe, have WildApricot record the
renewal invoice + payment, and have the renewal date advanced for the member
and their family/spouse.

## Goals

- Wire the portal's "Renew Membership" buttons to a real renewal flow.
- Charge the membership fee through the **same Stripe flow** the signup uses.
- Create a WildApricot renewal invoice, record the payment, and advance the
  member's `RenewalDue`.
- Advance the `RenewalDue` of the member's family/spouse members too.
- Mirror the signup architecture (Stripe sequence + a queued WA job).

## Non-Goals

- No redesign of the renewal modals (the 3-screen flow already exists in the UI).
- Lifetime memberships are **not renewable** — out of scope by definition.
- No change to the public signup flow's behavior (only a fee-table refactor).

## Decisions (from brainstorming)

| Topic | Decision |
|---|---|
| Renewal amount | The member's plan fee, from the `FEES` table in `MembershipController` (extracted to shared config). |
| New renewal date | End of next calendar year (annual). Checkomatic → +1 month. Lifetime → n/a. |
| Family/spouse dates | Update each family member's `RenewalDue` explicitly (found via the "Member Identifier" field). |
| Code location | New endpoints on `MemberPortalController`, behind `member.portal.auth`. |
| WA processing | A queued job (`ProcessMembershipRenewal`), like signup. |
| Checkomatic amount | The renewal modal includes a monthly-amount input (member enters it, like signup). |
| Lifetime | Non-renewable — "Renew Membership" buttons hidden for lifetime members; endpoints reject lifetime types. |
| Architecture | Approach A — `RenewalService` + `ProcessMembershipRenewal` job. |

## Architecture

### New files

- **`config/membership.php`** — the shared membership fee table, extracted from
  `MembershipController::FEES`:
  ```php
  return ['fees' => [
      'family'                 => ['cents' => 4000,   'label' => '$40.00'],
      'individual'             => ['cents' => 2500,   'label' => '$25.00'],
      'flat'                   => ['cents' => 2000,   'label' => '$20.00 / member'],
      'checkomatic_family'     => ['cents' => 1000,   'label' => '$10.00/mo'],
      'checkomatic_individual' => ['cents' => 1000,   'label' => '$10.00/mo'],
      'lifetime_family'        => ['cents' => 150000, 'label' => '$1,500.00'],
      'lifetime_individual'    => ['cents' => 100000, 'label' => '$1,000.00'],
  ]];
  ```

- **`app/Services/RenewalService.php`** — orchestrates a renewal:
  - `resolveTypeSlug(MemberProfile $profile): string` — maps the member's WA
    `MembershipLevel.Name` to a membership-type slug by inverting the `$nameMap`
    in `WildApricotService::resolveLevelId()`. Throws if it maps to a lifetime
    slug (caller turns that into a "not renewable" response).
  - `resolveFee(string $type, int $familyCount, ?float $checkomaticAmount): array`
    — three branches: `flat` → `(1 + $familyCount) * 2000` cents;
    `checkomatic_*` → `round($checkomaticAmount * 100)` cents; else →
    `config('membership.fees')[$type]['cents']`. Returns `{cents, label}`.
  - `buildSummary(MemberProfile $profile): array` — `{type, isCheckomatic, fee,
    newRenewalDate, familyCount}` for the modal.
  - `charge(int $contactId, MemberProfile $profile, string $paymentMethodId, ?float $checkomaticAmount): array`
    — creates the `Renewal` row, runs the Stripe sequence via `StripeService`,
    dispatches `ProcessMembershipRenewal` on success. Returns
    `{success, requires_action?, client_secret?, payment_intent_id?, renewal_id, message?}`.

- **`app/Jobs/ProcessMembershipRenewal.php`** — queued job (`tries=3`,
  `backoff=60`). Does the WildApricot side.

- **`database/migrations/xxxx_create_renewals_table.php`** + **`app/Models/Renewal.php`**
  — durable per-renewal record (the renewal analog of `PendingRegistration`).

### Modified files

- **`app/Http/Controllers/MemberPortalController.php`** — three thin endpoints:
  `renewSummary` (GET), `processRenewal` (POST), `renewStatus` (GET).
- **`routes/web.php`** — three routes in the `member-portal` group, behind
  `member.portal.auth`: `GET /renew/summary`, `POST /renew`,
  `GET /renew/status/{renewal}`.
- **`app/Http/Controllers/MembershipController.php`** — `FEES` constant replaced
  with `config('membership.fees')`. No behavior change.
- **`resources/views/member-portal/dashboard.blade.php`** — wire the existing
  "Renew Membership" buttons + 3-screen modal flow to the new endpoints with
  Stripe.js (`payment_method_id`, mirroring signup). Hide the buttons for
  lifetime members. The checkomatic renewal modal gets a monthly-amount input.

## Data Flow

### Opening the renewal modal — `GET /member-portal/renew/summary`
1. Controller loads the member's `MemberProfile` from the cached bundle
   (`MemberPortalService::getBundle()`).
2. Guard: lifetime type → `{renewable: false}`.
3. `RenewalService::buildSummary()` returns the type slug, fee `{cents, label}`
   (checkomatic `cents` pending until entered), the projected new renewal date
   (end of next calendar year), and the family-member count.
4. The modal renders the "Pending Payment $X" screen; checkomatic shows the
   monthly-amount input.

### Submitting payment — `POST /member-portal/renew`
1. Request carries Stripe `payment_method_id` and, for checkomatic,
   `monthly_amount`.
2. Controller validates, re-derives `contactId` from session, calls
   `RenewalService::charge()`.
3. `charge()` resolves the fee **server-side**, creates a `Renewal` row
   (`status=pending`), runs the Stripe sequence — `createCustomer` →
   `addPaymentMethodToCustomer` → `createPaymentIntent` (metadata: `contact_id`,
   `renewal_id`, `type`) → `processPayment` — with per-step try/catch and
   `stripeErrorFields()` decline handling, identical to `createCheckoutSession`.
4. Stripe success → `Renewal.status=paid`, store charge id; dispatch
   `ProcessMembershipRenewal($renewal)`; return `{success:true, renewal_id}`.
5. `requires_action` (3DS) → return `{requires_action, client_secret,
   payment_intent_id}`; JS completes the challenge and re-confirms.
6. Stripe failure → `Renewal.status=failed` + decline fields; return the
   friendly decline message (HTTP 402).

### WA processing — `ProcessMembershipRenewal` job (queued)
1. Idempotency: `if ($renewal->processed) return;`
2. `wa_step='invoice'` → `createMembershipInvoice(contactId, amount, type)`
   (already `OrderType=ExplicitRenewal`) → store `wa_invoice_id` →
   `setInvoiceNumberOnContact`.
3. `wa_step='payment'` → `recordPayment(contactId, invoiceId, amount, chargeId,
   paymentMethodId)` → `setPaymentProcessedOnContact`.
4. `wa_step='renewal'` → `updateMember(contactId, ['membership_type' => $type])`
   — advances the primary's `RenewalDue` (via `calcRenewalDate`).
5. `wa_step='family'` → `getFamilyMembers(contactId)` → loop
   `updateMember(familyId, ['membership_type' => $type])`. Per-member failures
   are logged and non-fatal.
6. `wa_step='done'`, `processed=true`, `status='processed'`; invalidate the
   cached member bundle.
Each failed step records `wa_step`/`wa_error`/`wa_error_at`, increments a retry
count, and rethrows so the queue retries — same pattern as
`ProcessWildApricotRegistration`.

### Success screen — `GET /member-portal/renew/status/{renewal}`
- Returns the `Renewal` state. `processed=true` → "Renewal Successful! Invoice
  Generated: INV-xxxx" + new renewal date. `paid` but not `processed` →
  "finalizing…" (job in flight). `failed` → the decline message.

## Renewal Model / `renewals` Table

Columns: `id`, `contact_id` (WA), `member_email`, `membership_type`,
`amount_cents`, `currency`, `stripe_customer_id`, `stripe_payment_method_id`,
`stripe_payment_intent_id`, `stripe_charge_id`, `status`
(`pending`/`paid`/`failed`/`processed`), `wa_invoice_id`, `wa_step`,
`processed` (bool), `retry_count`, `error_type`, `error_code`,
`error_decline_code`, `error_message`, `payment_method`, `card_brand`,
`card_last4`, `paid_at`, timestamps.

Both the Stripe charge and the job write to this row; `renewStatus` reads it.

## Fee Resolution

Replicates all three branches of `createCheckoutSession`:
1. **Standard** (`family`, `individual`) → `config('membership.fees')[$type]['cents']`.
2. **`flat`** → `(1 + familyCount) * 2000` cents; `familyCount` = number of
   family members from `getFamilyMembers()`.
3. **`checkomatic_*`** → `round($monthlyAmount * 100)` cents; `$monthlyAmount`
   from the renewal modal input, validated server-side.
Lifetime types are rejected before fee resolution.

## Error Handling

- **Stripe failures** — per-step try/catch as in `createCheckoutSession`; failed
  charge sets `Renewal.status=failed` and never dispatches WA work. Decline
  codes map to friendly messages; `requires_action` returns the client secret.
- **WA job failures (post-charge)** — each step wrapped; failure records
  `wa_step`/`wa_error`, increments retry count, rethrows → queue retries
  (`tries=3`). Idempotency guards make retries resume, never double-invoice.
- **Family-member updates are non-fatal** — one failed family `updateMember` is
  logged; the loop continues; the job still completes.
- **Exhausted retries** — `Renewal` stays `paid` + `processed=false` with the
  error recorded, available for a manual retry; the audit trail is intact.
- **Guards** — lifetime → 422 `{renewable:false}`; no `contact_id` → redirect to
  login; checkomatic `monthly_amount` validated `required|numeric|min:…`; the
  client amount is never trusted for non-checkomatic plans; Pay button disabled
  during the in-flight request; the `Renewal` row + idempotent job prevent
  duplicate processing.
- Cached bundle invalidated on successful processing.

## Testing

- **`RenewalService` tests** — `Http::fake()` WA, faked Stripe: `resolveFee`
  for all 3 branches, `resolveTypeSlug` incl. lifetime → throws, `buildSummary`
  shape, `charge()` produces `paid` Renewal + dispatches the job on success,
  `failed` on decline, `requires_action` passthrough.
- **`ProcessMembershipRenewal` job tests** — `Http::fake()` WA: creates invoice,
  records payment, advances primary `RenewalDue`, advances each family member,
  sets `processed`; idempotency (re-run skips completed steps); family-member
  failure is non-fatal.
- **Controller feature tests** — `renewSummary` fee/date; `processRenewal` with
  faked Stripe success returns `{success, renewal_id}` + creates the row;
  lifetime member → 422; `renewStatus` reflects job state.
- **Stripe** — faked, no real charges.
- **Manual smoke** — one renewal end-to-end against live WA + Stripe test mode.

## Open Items for Implementation

- Confirm the `StripeService` test seam used by existing signup tests so the
  renewal tests fake Stripe the same way.
- Confirm the WA `MembershipLevel.Name` strings on real member records so
  `resolveTypeSlug`'s inverse map is accurate (verify during the smoke test).

# Member Portal — WildApricot Data Integration

**Date:** 2026-05-16
**Status:** Approved design — ready for implementation planning

## Problem

The member portal authenticates members via email + OTP, but the dashboard and
profile pages display mostly **hardcoded placeholder data** (invoices of $20.00,
a fixed "Sarah Alam" spouse, static payment totals). After a member's OTP is
validated, the portal should fetch that member's **complete** record from
WildApricot and display real data: profile fields, membership status, invoices,
payment history, and family members — bound to the existing dashboard and
profile HTML.

## Goals

- Fetch the full member bundle from WildApricot when the OTP is validated.
- Display real data on the dashboard (Membership Status, Recent Invoices,
  Payment Overview, Quick Profile) and profile page (Membership Information,
  Primary Member form, Spouse Information).
- Allow profile edits to persist back to WildApricot.
- Keep all existing UI design, layout, colors, and responsive behavior **unchanged** —
  only data bindings change.

## Non-Goals

- No redesign of any dashboard/profile UI.
- No local-database mirroring of member data (session cache is sufficient).
- No support for WA native membership bundles (family uses the custom
  "Member Identifier" field — see Decisions).

## Decisions (from brainstorming)

| Topic | Decision |
|---|---|
| Invoices source | WildApricot Invoices **and** Payments API. |
| Family member linking | Custom **"Member Identifier"** field — related contacts store the primary member's WA contact ID. |
| Profile editing | "Save Changes" **persists** to WA via existing `updateMember()`. |
| Performance | **Cache the full bundle per session** after login (10-minute TTL); pages read from cache; cache miss re-assembles. |
| Missing data | **Hide empty sections gracefully** — no spouse hides the spouse card/form; no invoices shows a "No invoices yet" empty state; blank fields render `—`. |
| Architecture | **Approach A** — dedicated service + `MemberProfile` view-model DTO. |

## Architecture

Three layers sit between WildApricot and the Blade views.

### New files

- **`app/Services/WildApricotService.php`** — extended with three methods:
  - `getInvoicesForContact(int $contactId): array`
    → `GET /accounts/{accountId}/invoices?contactId={contactId}`
  - `getPaymentsForContact(int $contactId): array`
    → `GET /accounts/{accountId}/payments?contactId={contactId}`
  - `getFamilyMembers(int $primaryContactId): array`
    → `GET /accounts/{accountId}/contacts?$filter=<Member Identifier code> eq '{id}'`
    (the "Member Identifier" system code is resolved via the existing
    `getFieldSystemCode()` dynamic lookup, not hardcoded).

- **`app/Support/MemberProfile.php`** — a plain view-model class. Constructed
  from a raw WA bundle array; exposes clean typed accessors so Blade views never
  touch raw WA `FieldValues`. It is the **single** place that knows WA's data shape.

- **`app/Services/MemberPortalService.php`** — orchestrator:
  - `assembleBundle(int $contactId): array` — calls the WA methods, returns a
    cache-ready raw bundle.
  - `updateProfile(int $contactId, array $data)` — delegates to
    `WildApricotService::updateMember()`.
  - `updateFamilyMember(int $contactId, array $data)` — same for a spouse/family
    contact.

### Modified files

- **`MemberPortalController.php`** — `verifyOtp()` assembles + caches the bundle;
  `dashboard()`/`profile()` read cache → build `MemberProfile`; new
  `updateProfile()` action for the save endpoint.
- **`routes/web.php`** — add `POST /member-portal/profile/update`.
- **`dashboard.blade.php`**, **`profile.blade.php`**,
  **`partials/profile-content.blade.php`** — replace hardcoded `@php` blocks with
  `MemberProfile` accessors. No visual/layout/markup-structure changes.

## Data Flow

### At OTP verification (`verifyOtp`)
1. Once the OTP is confirmed valid, call `MemberPortalService::assembleBundle($contactId)`.
2. It fetches: fresh full contact → `getFamilyMembers` → `getInvoicesForContact`
   → `getPaymentsForContact`. Family contact rows already carry `FieldValues`,
   so no recursive per-member fetch is needed.
3. Store the raw bundle
   `['contact'=>…, 'family'=>[…], 'invoices'=>[…], 'payments'=>[…]]`
   via `Cache::put("member_portal_bundle_{$contactId}", $bundle, now()->addMinutes(10))`.
   The contact ID (cache key) is kept in the session.
4. If any **secondary** call fails, the bundle still stores with whatever
   succeeded (partial data) and logs the failure — login never blocks.

### At dashboard / profile load
1. Controller reads the cache by key. **Cache miss** (TTL expired) →
   re-assemble and re-cache transparently.
2. Wrap the raw bundle in `new MemberProfile($bundle)`; pass that single object
   to the view via `compact('profile')`.
3. Views call accessors — `$profile->invoices`, `$profile->spouse`, etc.

### At profile save (`POST /member-portal/profile/update`)
1. Validate submitted fields server-side (required, email format, date).
2. `MemberPortalService::updateProfile()` → `WildApricotService::updateMember()`
   for the primary; `updateFamilyMember()` for the spouse if present.
3. On success, **invalidate the cache key** so the next load re-fetches fresh data.
4. Return JSON `{success, message}` — the existing JS save-banner consumes it.

### Refresh
An optional `?refresh=1` query param on dashboard/profile forces cache
invalidation + re-assembly, letting the user pull fresh data without re-login.

## MemberProfile View-Model

Constructed with `new MemberProfile(array $bundle)`. A private
`field(string $name)` helper scans `FieldValues[]` matching `FieldName` OR
`SystemCode`, unwrapping choice values (`{Id,Label}` → `Label`).

### Field mapping

| Accessor | WA source |
|---|---|
| `firstName`, `lastName`, `email`, `status` | top-level contact keys |
| `phone` | `Phone` or Cell Phone `custom-9967571` |
| `street` | Street Address `custom-9967566` |
| `city` | City `custom-9967567` |
| `state` | State `custom-9967569` |
| `zip` | ZIP `custom-9967570` |
| `dob` | Date of Birth `custom-10694881` |
| `txId` | TX DL/ID `custom-17846913` |
| `zone` | Zone/Center `custom-9967573` |
| `level` | `MembershipLevel.Name` |
| `memberSince` | `MemberSince` FieldValue |
| `renewalDue` | `RenewalDue` FieldValue |
| `yearlyFee` | `MembershipLevel` fee, or `Membership fee` FieldValue |

### Derived properties

- `fullName` — `firstName . ' ' . lastName`, trimmed.
- `isExpired` — `status` ∈ {Expired, Lapsed, Overdue} (reuses dashboard logic).
- `daysLeft` / `daysOverdue` — Carbon diff of `renewalDue` vs today, sign-split.
- `family[]` — each family contact wrapped in its own `MemberProfile`.
  `spouse` = first family member, or `null`.
- `invoices[]` — each normalized to `{number, date, amount, url, isPaid}` from WA
  invoice fields `DocumentNumber`, `CreatedDate`, `Value`, `IsPaid`, `Id`.
- `payments[]` — normalized to `{date, amount}` from WA payments.
- `nextPayment` — earliest unpaid invoice `{amount, date}`, else null.
- `lastPayment` — most recent payment `{amount, date}`, else null.
- `paidThisYear` — sum of payments in the current calendar year.
- `paidAllTime` — sum of all payments.

### Empty-data handling

- `hasSpouse()`, `hasFamily()`, `hasInvoices()` boolean helpers — views use these
  to conditionally render whole sections.
- Blank string accessors return `''`; views render `—` for those.
- Formatting helpers `memberSinceFormatted()`, `renewalFormatted()`,
  `dobFormatted()` return `F d, Y` or `''`.
- Accessors never throw: missing keys → `''`, bad dates → `''` (try/catch around
  Carbon), non-array `FieldValues` → treated as empty.

## View Binding Changes

No layout, CSS, color, or markup-structure changes — only `@php` data blocks and
`{{ }}` values change.

### `dashboard.blade.php`
- Replace the `@php` block with the single `$profile` object.
- **Membership Status card** — `$profile->level`, `->status`,
  `->renewalFormatted()`, `->daysLeft`/`->daysOverdue`, `->memberSinceFormatted()`,
  `->yearlyFee`, `->txId`. The active/expired toggle keeps working
  (`isExpired` drives it server-side; JS `setMembershipStatus()` unchanged).
- **Recent Invoices** — `@forelse($profile->invoices as $inv)` rendering
  `number`, `date`, `amount`, `url`; `@empty` → "No invoices yet" empty state
  inside the same card.
- **Payment Overview** — Next ← `nextPayment`, Last ← `lastPayment`,
  This Year ← `paidThisYear`, All Time ← `paidAllTime`; `—`/`$0.00` when null.
- **Quick Profile card** — `fullName`, `email`, `phone`, `city`/`state`.

### `partials/profile-content.blade.php` (shared by `#profilePage` and `/profile`)
- **Membership Information card** — same accessors as the dashboard status card.
- **Primary Member Information form** — each input `value` bound to the matching
  accessor (`firstName` … `zone`).
- **Spouse Information** — wrapped in `@if($profile->hasSpouse())`; the spouse
  form and right-rail Spouse Profile card both render from `$profile->spouse`
  (itself a `MemberProfile`). No spouse → both hidden; mobile `order` rules skip them.
- The "same as primary" JS link and spouse-name sync keep working unchanged.

### Save flow
The existing `setupForm()` JS is pointed at `POST /member-portal/profile/update`
with form fields + CSRF, consuming the JSON `{success,message}` to show the
existing green banner or an inline error.

## Error Handling

- **WA API failures during bundle assembly** — each secondary call (family,
  invoices, payments) is wrapped independently; failure logs `Log::warning` and
  yields an empty array for that slice. The primary contact fetch is the only
  hard dependency; if it fails, `$profile` is built from whatever the session
  held and the page shows graceful empty states.
- **Cache miss on page load** — transparently re-assembles; no user-facing error.
- **Session without `contact_id`** — guarded with a redirect to
  `member-portal.login` (preserves existing behavior).
- **Profile save failures** — `updateMember()` throws `RuntimeException` on WA
  rejection; controller catches it, returns `{success:false, message:'Could not
  save changes. Please try again.'}` with HTTP 422; JS shows an inline error.
  Validation errors (bad email/date) return field-level messages the same way.
- **Malformed WA data** — `MemberProfile` accessors never throw.

## Testing

- **`MemberProfile` unit tests** — fixture bundles assert field mapping (choice
  unwrap, custom codes), `isExpired` per status, `daysLeft`/`daysOverdue`
  sign-split, invoice/payment normalization, `nextPayment`/`lastPayment`
  selection, `paidThisYear`/`paidAllTime` sums, `hasSpouse()`/`hasInvoices()`
  with full vs empty bundles.
- **`MemberPortalService` tests** — HTTP faked via `Http::fake()`; assert
  `assembleBundle()` aggregates correctly and a faked invoices-call failure still
  returns a bundle with the other slices intact.
- **Controller feature tests** — faked WA HTTP; assert `verifyOtp` populates the
  cache, `dashboard`/`profile` render with real bindings, cache-miss
  re-assembles, `updateProfile` invalidates the cache and returns correct JSON
  for success and failure.
- **Manual smoke check** — one real WA member through OTP → dashboard → profile
  → edit-save, to confirm live field codes against the real account.

## Open Items for Implementation

- Confirm exact WA invoice/payment JSON field names against the live account
  during the manual smoke test; adjust `MemberProfile` normalization if WA uses
  different keys (e.g. `Value` vs `Amount`).
- Confirm the "Member Identifier" and "Membership fee" field names resolve via
  `getFieldSystemCode()`; if not present, surface during the smoke test.

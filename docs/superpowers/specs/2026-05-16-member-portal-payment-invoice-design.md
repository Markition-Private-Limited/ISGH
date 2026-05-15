# Member Portal — Payment & Invoice Page

**Date:** 2026-05-16
**Status:** Approved

## Goal

Add a dedicated "Payment & Invoice" page to the member portal that displays
every invoice fetched from WildApricot, and wire it into the sidebar (and
mobile bottom-nav).

## Architecture

A new route `GET /member-portal/payments` →
`MemberPortalController::payments()` → new view `member-portal/payments.blade.php`.

It reuses the existing auth middleware (`member.portal.auth`),
`MemberPortalService` bundle assembly/caching, and the `MemberProfile`
view-model. No new WildApricot API work is required — invoices and payments
already flow into the cached bundle via `MemberPortalService::assembleBundle()`
and are normalized by `MemberProfile`.

## Components

### 1. Shared sidebar partial — `partials/sidebar.blade.php`

The sidebar markup currently lives inline in `dashboard.blade.php` (and is
partly duplicated for the mobile drawer). Extract it into a partial accepting
an `$active` parameter (`'dashboard'`, `'profile'`, `'payments'`).

- The dashboard includes the partial and preserves its existing `showPage()`
  SPA toggle behavior for Dashboard/Profile.
- The payments page includes the partial with real `href` links.
- "Dashboard" → `route('member-portal.dashboard')`
- "Profile" → `route('member-portal.dashboard')` (Profile remains an SPA
  section on the dashboard)
- "Payment & Invoice" → `route('member-portal.payments')`, marked `active` on
  the new page.

This is a targeted refactor that removes existing duplication; no unrelated
sidebar changes.

### 2. Payments page — `payments.blade.php`

Three sections matching the provided mockup:

- **Payment Summary** — two cards:
  - "Total Paid (All Time)" = `$profile->paidAllTime()`
  - "Current Year" = `$profile->paidThisYear()`
- **Next Renewal** — green gradient card:
  - `$profile->renewalFormatted()`, `$profile->yearlyFee`,
    `$profile->daysLeft()` remaining.
- **Invoice History** — table over `$profile->invoices` with columns:
  *Invoice Number, Membership Type, Amount, Payment Date, Billing Period,
  Action (View)*.

### 3. Billing period logic — `MemberProfile::billingPeriod()`

New helper `billingPeriod(array $invoice): string` classifying by
`$this->level` (the WildApricot membership-level name):

- name contains `lifetime` → `''` (blank — lifetime memberships do not renew)
- name contains `checkomatic` → created month + 1 month
  (e.g. `"Jan 2026 – Feb 2026"`)
- otherwise (annual) → created month + 1 year
  (e.g. `"Jan 2026 – Jan 2027"`)

Classification is case-insensitive. Returns `''` when the invoice date is
unparseable.

### 4. Client-side search + pagination

Vanilla JS, 10 rows per page:

- Search box filters table rows by invoice number (case-insensitive substring).
- Pager shows "Page X of Y" with Previous/Next buttons.
- Operates entirely in-browser on already-rendered rows — no API calls on
  search or page change.

### 5. Mobile bottom-nav

The bottom-nav "Payments" item (currently a dead `<a href="#">`) links to
`route('member-portal.payments')`.

## Data Flow

```
payments() controller
  → MemberPortalService::getBundle(contactId)   [cached, 10 min]
  → new MemberProfile($bundle)
  → payments.blade.php renders all invoice rows
  → JS handles search / pagination in-browser
```

`?refresh=1` invalidates the cache (same convention as dashboard/profile).

## Error / Empty Handling

- No `member_portal_contact_id` in session → redirect to
  `member-portal.login` (same guard as `dashboard()`/`profile()`).
- Empty invoice list → table renders a centered "No invoices yet" row;
  summary cards show `$0.00`.
- Invoice `View` link uses WildApricot's `Url`; when missing it is `#` and the
  link is rendered disabled.

## Out of Scope

- Custom invoice PDF generation or custom invoice numbering (use WildApricot
  `DocumentNumber` / `Url` as-is).
- Server-side pagination.
- The other dead sidebar items (ISGH Records, Newsletter, Financial Report,
  Updates, Nominees Training) remain unlinked.

## Files Touched

- `routes/web.php` — add `payments` route.
- `app/Http/Controllers/MemberPortalController.php` — add `payments()` method.
- `app/Support/MemberProfile.php` — add `billingPeriod()` helper.
- `resources/views/member-portal/partials/sidebar.blade.php` — new partial.
- `resources/views/member-portal/dashboard.blade.php` — use sidebar partial,
  link mobile bottom-nav "Payments" item.
- `resources/views/member-portal/payments.blade.php` — new view.

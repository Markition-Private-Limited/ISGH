# Member-Portal Routing & Logout

**Date:** 2026-05-16
**Status:** Approved

## Goal

Wire every sidebar nav item, mobile bottom-nav item, and quicklink across all
8 member-portal pages to its real route, and add a working logout button to
the sidebar.

## Context

All routes already exist in `routes/web.php` under the `member-portal.` prefix:
`dashboard`, `profile`, `payments`, `records`, `newsletter`,
`financial-report`, `updates`, `nominees-training`, and `logout` (POST).
`MemberPortalController::logout()` already clears the `member_portal_*` session
keys, regenerates the session, and redirects to login. No backend work is
required.

Current state of the views:

- `partials/sidebar.blade.php` — shared partial with 8 nav items, but only
  Dashboard/Profile/Payments are wired; the other 5 are dead `#` links. Used
  only by `dashboard.blade.php` and `payments.blade.php`.
- `profile.blade.php` and the 5 static pages (`isgh-records`, `newsletter`,
  `financial-report`, `updates`, `nominees-training`) each carry their own
  **inline copy** of the sidebar and the mobile bottom-nav, all with dead `#`
  links.
- Quicklinks: `dashboard.blade.php` has 5, `profile.blade.php` has 4 — all dead
  `#` links except the renew / change-level items, which are JS-driven modal
  triggers.
- No logout button exists anywhere.

## 1. Shared sidebar partial — `partials/sidebar.blade.php`

Extend the partial to be the single sidebar for all 8 pages.

- Wire all 8 nav items to real routes:
  - Dashboard → `member-portal.dashboard`
  - Profile → `member-portal.profile`
  - Payment & Invoice → `member-portal.payments`
  - ISGH Records → `member-portal.records`
  - Isgh Newsletter → `member-portal.newsletter`
  - Financial Report → `member-portal.financial-report`
  - Updates → `member-portal.updates`
  - Nominees Training & Orientation → `member-portal.nominees-training`
- `$active` accepts all 8 keys: `dashboard`, `profile`, `payments`, `records`,
  `newsletter`, `financial-report`, `updates`, `nominees-training`. The
  matching item gets the `active` CSS class.
- **SPA mode preserved.** `$mode` keeps its two values:
  - `mode='spa'` (dashboard only) — Dashboard and Profile use
    `onclick="showPage(...)"` with `data-page-link`; the other 6 items are real
    `href` links.
  - `mode='links'` (all other 7 pages) — all 8 items are real `href` links.
- **Logout button** added at the bottom of `.sidebar-nav`, visually separated
  from the nav items. It is a `<form method="POST"
  action="{{ route('member-portal.logout') }}">` containing `@csrf` and a
  styled submit button (a form, not a link, because the route is POST). The
  button reuses `.nav-item` styling plus a `.nav-logout` modifier with a logout
  icon and a red-tinted hover state.

## 2. Mobile bottom-nav partial — `partials/bottom-nav.blade.php` (new)

Extract the duplicated inline mobile bottom-nav into a new partial taking
`$active` and `$mode`, mirroring the sidebar's two-mode logic.

- It shows 5 items — the same subset already present in the inline copies:
  Dashboard, Profile, Payments, Records, Financial Report (labelled "Reports").
- `mode='spa'`: Dashboard and Profile use `onclick="showPage(...)"`; the other
  3 are real links. `mode='links'`: all 5 are real links.
- `$active` highlights the matching item with `bn-item active`.
- No logout in the bottom-nav (no room; the sidebar is reachable on mobile via
  the hamburger).

## 3. Per-page changes (8 view files)

- `profile.blade.php` and the 5 static pages: replace the inline
  `<aside class="sidebar">…</aside>` with
  `@include('member-portal.partials.sidebar', ['active'=>'<key>', 'mode'=>'links'])`
  and replace the inline `<nav class="bottom-nav">…</nav>` with
  `@include('member-portal.partials.bottom-nav', ['active'=>'<key>', 'mode'=>'links'])`.
- `dashboard.blade.php`: already includes the sidebar (`mode='spa'`); replace
  its inline bottom-nav with the new bottom-nav partial include
  (`mode='spa'`).
- `payments.blade.php`: already includes the sidebar (`mode='links'`). It
  currently has no mobile bottom-nav at all — add the bottom-nav partial
  include (`mode='links'`, `active='payments'`) so mobile navigation works
  there too.
- Each page passes its own `$active` key so the correct nav item highlights.

The CSS classes the partials rely on (`sidebar`, `nav-item`, `active`,
`bottom-nav`, `bn-item`, etc.) already exist in every page's inline `<style>`
block, so no CSS needs to move. The new `.nav-logout` styles are added to each
page's stylesheet (or, where practical, only to pages that render the sidebar
— every page does).

## 4. Quicklinks — `dashboard.blade.php`, `profile.blade.php`

Replace dead `#` hrefs with real routes for **navigation** quicklinks only:

- "View Invoices" / "Payment History" → `member-portal.payments`
- "Update Profile" → on `profile.blade.php` a real `member-portal.profile`
  link; on `dashboard.blade.php`, `onclick="showPage('profile')"` to stay
  consistent with the dashboard's SPA behavior.
- Any dashboard / records-type quicklink → its corresponding route.

**Modal-trigger quicklinks are left untouched** — "Renew Membership"
(`.ql-renew-link`) and "Change Level" (`.ql-change-level`) keep their JS
classes and `#` href so the renewal / level-change modals still open.

## 5. Logout flow

No backend change. The new sidebar form POSTs to `member-portal.logout` with a
CSRF token (`@csrf`). `MemberPortalController::logout()` already forgets the
`member_portal_authenticated`, `member_portal_token`, `member_portal_email`,
and `member_portal_contact_id` session keys, regenerates the session, and
redirects to `member-portal.login`.

## Error / edge handling

- Logout on an already-expired session still succeeds — `logout()` forgetting
  absent keys is a no-op and it redirects to login regardless.
- `$active` with an unknown/omitted key simply highlights nothing — no error.
- `$mode` defaults to `'links'` when omitted (existing partial behavior,
  preserved for the new bottom-nav partial too).

## Out of scope

- No removal of the dashboard's embedded `#profilePage` section, its scoped
  CSS, or `showPage()` — SPA mode stays.
- No changes to the renewal / level-change modals or their JavaScript.
- No new routes or controller methods.
- No restyling of the sidebar/bottom-nav beyond the new `.nav-logout` button.

## Testing

Feature tests (`tests/Feature/MemberPortalIntegrationTest.php`):

- Each of the 8 authenticated GET routes returns 200.
- The logout POST clears the session (`member_portal_authenticated` absent
  afterward) and redirects to `member-portal.login`.

Manual verification: on every page, click each sidebar item, each bottom-nav
item, and each quicklink; confirm navigation lands on the right page with the
right item highlighted; confirm the logout button ends the session and returns
to the login page.

## Files touched

- `resources/views/member-portal/partials/sidebar.blade.php` — wire 8 routes,
  add logout form.
- `resources/views/member-portal/partials/bottom-nav.blade.php` — new partial.
- `resources/views/member-portal/dashboard.blade.php` — use bottom-nav partial,
  route quicklinks, add `.nav-logout` CSS.
- `resources/views/member-portal/profile.blade.php` — use both partials, route
  quicklinks, add `.nav-logout` CSS.
- `resources/views/member-portal/payments.blade.php` — add bottom-nav partial
  (none exists today), add `.nav-logout` CSS, add bottom-nav CSS classes if
  absent.
- `resources/views/member-portal/isgh-records.blade.php` — use both partials,
  add `.nav-logout` CSS.
- `resources/views/member-portal/newsletter.blade.php` — use both partials, add
  `.nav-logout` CSS.
- `resources/views/member-portal/financial-report.blade.php` — use both
  partials, add `.nav-logout` CSS.
- `resources/views/member-portal/updates.blade.php` — use both partials, add
  `.nav-logout` CSS.
- `resources/views/member-portal/nominees-training.blade.php` — use both
  partials, add `.nav-logout` CSS.
- `tests/Feature/MemberPortalIntegrationTest.php` — route + logout tests.

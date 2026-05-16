# Share Renewal & Level-Change Modals with the Profile Page

**Date:** 2026-05-16
**Status:** Approved

## Goal

Make the member profile page's "Renew Membership", "Change Membership Level",
and "Change Level" buttons open the same renewal and level-change modals the
dashboard already uses. Achieve this by extracting both modals into shared
Blade partials, so the dashboard and the profile page render the identical
modal from one source.

## Context

`resources/views/member-portal/dashboard.blade.php` currently contains, inline:

- **Renewal modal** — `<div class="renew-overlay" id="renewModal">` HTML
  (dashboard lines ~1906–1980), gated by `@unless($isLifetime)`, plus its
  self-contained `<script>` IIFE (lines ~1981–2275) and a
  `<script src="https://js.stripe.com/v3/">` tag.
- **Level-change modal** — `<div class="renew-overlay" id="levelModal">` HTML
  (lines ~2279–2451), plus its self-contained `<script>` IIFE (lines
  ~2452–~2950). No lifetime gate, no Blade variables in the markup.
- **Modal CSS** — lines 1016–1314 of the `<style>` block: `.renew-overlay`
  through `.lvl-family-empty`. Lines 1016–1141 are the renewal modal CSS plus
  the shared base classes (`.renew-overlay`, `.renew-modal`, `.renew-screen`,
  `.renew-btn*`, `.renew-icon-circle`, etc.). Lines 1142–1314 are the
  level-change-specific `.lvl-*` rules, which depend on the shared base.

Both modal JS modules are **self-contained IIFEs**: each begins with
`const modal = document.getElementById('<id>'); if (!modal) return;`, reads its
endpoints via Blade `route()` / `config()` helpers, and binds its open-triggers
via `document.querySelectorAll('.btn-renew, .btn-renew-mobile, .ql-renew-link')`
(renewal) and `document.querySelectorAll('.ql-change-level')` (level change).
They have no dependency on dashboard-only JavaScript variables.

The renewal modal HTML uses exactly one Blade expression — `$isExpired` in the
confirm-screen title. The level-change modal HTML uses no Blade variables.

`profile.blade.php` is a standalone HTML document. It has three currently inert
buttons: a "Change Membership Level" button (`btn-change`, near the membership
info), a "Renew Membership" quicklink, and a "Change Level" quicklink. Its
controller (`MemberPortalController::profile()`) passes a `$profile`
(`App\Support\MemberProfile`) — the same object the dashboard receives.

## Architecture

Extract each modal — HTML + CSS + JS — into a self-contained Blade partial.
Both `dashboard.blade.php` and `profile.blade.php` `@include` the partials. One
source of truth; no duplication. The modal JS IIFEs are unchanged (pure move).

## Components

### 1. `partials/renew-modal.blade.php` (new)

Contains, in this order:

- A `<style>` block with the renewal + shared-base modal CSS (dashboard lines
  1016–1141: `.renew-overlay` … `.renew-invoice`).
- A `@php $isLifetime = stripos(($profile->level ?? ''), 'lifetime') !== false;
  $isExpired = $profile->isExpired(); @endphp` header so the partial is
  self-sufficient (the partial must not assume the including page already
  computed these).
- The `@unless($isLifetime)` … `@endunless`-wrapped
  `<div class="renew-overlay" id="renewModal">` HTML block (the renewal modal's
  one Blade reference, `$isExpired`, resolves from the header).
- The `<script src="https://js.stripe.com/v3/"></script>` tag.
- The renewal-modal `<script>` IIFE.

Requires `$profile` in scope. Both including pages already supply it.

### 2. `partials/level-modal.blade.php` (new)

Contains, in this order:

- A `<style>` block with the level-change CSS (dashboard lines 1142–1314: the
  `.lvl-*` rules). These depend on the shared base classes, which are always
  present because every page that includes the level-modal partial also
  includes the renew-modal partial (which carries the base).
- The `<div class="renew-overlay" id="levelModal">` HTML block (no Blade
  variables, no lifetime gate).
- The level-change `<script>` IIFE.

Requires no view variables.

### 3. `dashboard.blade.php` changes

- Delete the modal CSS from the inline `<style>` block (lines 1016–1314,
  inclusive — from `.renew-overlay {` through the `.lvl-family-empty { … }`
  rule, leaving the `</style>` that follows intact).
- Delete the inline renewal modal block: the `{{-- ── Renewal modal ── --}}`
  comment, the `@unless($isLifetime)` … `@endunless` wrapping the
  `#renewModal` HTML, the `<script src="…stripe…">` tag, and the renewal
  `<script>` IIFE.
- Delete the inline level-change modal block: the
  `{{-- ── Level-change modal ── --}}` comment, the `#levelModal` HTML, and the
  level-change `<script>` IIFE.
- In place of the two deleted modal blocks (near the end of `<body>`, before
  `</body>`), add:
  ```blade
  @include('member-portal.partials.renew-modal')
  @include('member-portal.partials.level-modal')
  ```
- The dashboard's `@php` block already computes `$isLifetime` and `$isExpired`
  for its own use elsewhere — leave that block as is. Blade `@include` shares
  the parent's variable scope, so the renew-modal partial's `@php` header
  re-assigns `$isLifetime` / `$isExpired`. This is harmless: it computes the
  exact same values (`stripos($profile->level, 'lifetime')` and
  `$profile->isExpired()`), and the partial is included near the end of
  `<body>` after every other use of those variables on the dashboard, so no
  earlier dashboard logic observes the re-assignment.

Net effect: the dashboard renders the identical modals via the partials; its
file shrinks by ~1100 lines.

### 4. `profile.blade.php` changes

- Add the two includes near the end of `<body>` (before `</body>`, after the
  page's existing content/scripts):
  ```blade
  @include('member-portal.partials.renew-modal')
  @include('member-portal.partials.level-modal')
  ```
- Wire the three buttons by adding the trigger classes the modal JS already
  binds to — no new JavaScript:
  - "Renew Membership" quicklink — add class `ql-renew-link`.
  - "Change Level" quicklink — add class `ql-change-level`.
  - "Change Membership Level" button (`btn-change`) — add class
    `ql-change-level`.
- No modal CSS is added to `profile.blade.php` directly — the partials carry
  their own `<style>` blocks.

## Data Flow

Modal JS IIFE → endpoints from `route()` (`member-portal.renew*`,
`member-portal.change-level*`) → existing controller actions. Unchanged. Both
the dashboard and profile controllers supply `$profile`; the renew-modal
partial derives `$isLifetime` / `$isExpired` from it.

## Lifetime Members

The renewal modal stays gated `@unless($isLifetime)` inside the renew-modal
partial. For a lifetime member, `#renewModal` is not rendered on either page.
The renewal JS IIFE early-returns (`if (!modal) return`), so clicking a
"Renew Membership" trigger does nothing — identical to current dashboard
behavior. The level-change modal is always rendered for everyone.

## Error / Edge Handling

- Each modal JS IIFE already early-returns when its modal element is absent —
  so a lifetime member's missing renewal modal causes no JS error.
- The renew-modal partial's `@php` header uses `$profile->level ?? ''` so a
  null/edge `$profile` cannot throw.
- A `<style>` block placed in the document body is valid HTML and renders
  correctly in all target browsers (the codebase already places `<script>`
  blocks mid-body).

## Out of Scope

- No changes to the renewal / level-change controllers, routes, or business
  logic.
- No changes to the modal screens, flows, or JavaScript behavior — this is a
  pure extraction plus re-wiring of triggers.
- The dashboard's existing `.btn-renew` / `.btn-renew-mobile` triggers and its
  quicklink triggers are unchanged.
- No restyling of the profile buttons beyond adding the trigger classes.

## Testing

Feature tests (`tests/Feature/MemberPortalIntegrationTest.php`):

- The profile page renders 200 and its HTML contains `id="renewModal"` and
  `id="levelModal"` (for a non-lifetime member).
- The dashboard page still renders 200 and still contains `id="renewModal"`
  and `id="levelModal"` (the extraction must not change dashboard output).
- A lifetime member's profile page renders 200, contains `id="levelModal"`,
  and does NOT contain `id="renewModal"`.

The existing `MemberPortalIntegrationTest` suite must continue to pass
unchanged.

Manual verification: on the profile page, "Renew Membership" opens the renewal
modal, "Change Membership Level" and "Change Level" each open the level-change
modal; both modals' flows work end-to-end; a lifetime member sees no renewal
modal.

## Files Touched

- `resources/views/member-portal/partials/renew-modal.blade.php` — new.
- `resources/views/member-portal/partials/level-modal.blade.php` — new.
- `resources/views/member-portal/dashboard.blade.php` — remove inline modal
  CSS + HTML + JS; add two `@include`s.
- `resources/views/member-portal/profile.blade.php` — add two `@include`s;
  add trigger classes to the three buttons.
- `tests/Feature/MemberPortalIntegrationTest.php` — modal-presence tests.

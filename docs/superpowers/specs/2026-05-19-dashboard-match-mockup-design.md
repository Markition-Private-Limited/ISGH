# Member Dashboard — Match Target Mockup

**Date:** 2026-05-19
**File affected:** `resources/views/member-portal/dashboard.blade.php`

## Goal

Bring the live member portal dashboard in line with the target mockup. Comparing
the live dashboard against the mockup, the differences are box sizing and the
green gradient shade. No new features — purely visual refinement.

## Differences identified

1. **Recent Invoices card stretches too tall.** The `.row-2` grid stretches the
   invoices card to match the taller Payment Overview card, leaving a large
   empty white area below the invoice rows. The mockup shows the card hugging
   its content.
2. **Two-column split slightly off.** The invoices column is a touch too narrow
   relative to Payment Overview compared to the mockup.
3. **Green gradient too dark.** The featured "Membership Type" tile and the
   active sidebar nav item use a near-black forest green
   (`#0e7a52 → #0a5a3d`). The mockup uses a brighter, more vivid emerald.

## Changes

All changes are CSS-only, inside the existing `<style>` block. No markup or PHP
changes.

### 1. Brighter emerald gradient

Replace the dark gradient with `linear-gradient(135deg, #15a36b 0%, #0c7a52 100%)`
— lighter tone top-left — in **both** rules so they stay identical:

- `.status-tile.featured` (Membership Type tile)
- `.nav-item.active` (active sidebar item)

The `.nav-item.active` box-shadow is left unchanged. The expired-state override
`.status-card.is-expired .status-tile.featured` (the red gradient) is **not**
touched — only the normal green changes.

### 2. Recent Invoices card hugs its content

Add `align-items: start;` to `.row-2`. Each card then sizes to its own content
and the two cards top-align, rather than the invoices card being stretched.

### 3. Rebalance the column split

Change the `.row-2` `grid-template-columns` first track from `360px` to `380px`.
Payment Overview keeps the `minmax(0, 1fr)` track and stays comfortably wide.

## Responsive

All three changes are desktop-layout-safe and preserve existing responsive
behavior:

- `@media (max-width: 960px)` already collapses `.row-2` to a single column —
  unaffected by the `align-items` and column-width changes.
- The `≤520px` tile rules do not touch the gradient hex or `align-items`.
- The gradient is a color change and applies correctly at all viewport sizes.

## Testing

Manual visual verification:

1. Run `php artisan view:clear` first — the Blade view cache can mask template
   changes.
2. Desktop (≥1180px): featured tile + active nav show the brighter emerald;
   Recent Invoices card ends right after its last row with no empty space;
   columns rebalanced.
3. ≤960px and ≤520px: confirm the `.row-2` single-column collapse and tile
   restyling still work.

## Out of scope

- The grey browser frame and chat-bubble widget in the mockup are external
  (Figma frame / Intercom-style widget), not part of the page.
- Status tiles and payment tiles sizing — judged correct in the comparison.
- The Membership Status card's light green→white background gradient — correct.

---

# Profile Page — Match Profile Card Height to Edit Form

**Added:** 2026-05-19
**Files affected:** `resources/views/member-portal/partials/profile-content.blade.php`
(markup), `resources/views/member-portal/dashboard.blade.php` (`#profilePage`
CSS).

## Goal

On the Profile page, each right-column Profile card should match the height of
its paired left-column edit form: Primary Profile card ↔ Primary Member
Information form, Spouse Profile card ↔ Spouse Information form. Currently the
Profile cards size to their own content (avatar + name), leaving a large empty
gradient area beside the taller form.

## Constraint

`#profilePage` is a two-column grid, but each column is an independent flex
stack (`.left-col`, `.right-col`). The Profile cards have no row relationship
to the forms. To equalise heights per pair, each left/right pair must occupy
the same grid row.

## Changes

### 1. Markup — remove column wrappers

In `profile-content.blade.php`, drop the `.left-col` and `.right-col` wrapper
elements so the six cards become direct children of `#profilePage`, ordered as
left/right pairs for grid auto-placement:

1. Membership Information (left, row 1)
2. Quick Links (right, row 1)
3. Primary Member Information form (left, row 2)
4. Primary Profile card (right, row 2)
5. Spouse Information form (left, row 3)
6. Spouse Profile card (right, row 3)

The `@if($profile->hasSpouse())` guards stay; without a spouse, row 3 is absent.

### 2. CSS — row-based grid

`#profilePage` inherits `grid-template-columns: minmax(0,1fr) 320px` from
`.content`. Add `grid-auto-flow: row;`, `align-items: stretch;`, and the
column `gap` (22px) directly on `#profilePage`. Remove the now-dead
`#profilePage .left-col, #profilePage .right-col { display:flex... }` rule.

### 3. CSS — centre Profile card content

`#profilePage .profile-card` gets `display:flex; flex-direction:column;
justify-content:center; align-items:center;` so its content centres in the
grid-stretched height.

### 4. Mobile — preserve existing behaviour

The `≤768px` block uses `.left-col/.right-col { display:contents }` plus
`order:` rules. With the wrappers removed, update that block to drop the
`display:contents` rule and re-point the `order:` rules to the card classes
directly (`.info-card`, `.card:nth-of-type(n)`, `.profile-card:nth-of-type(n)`).
Net mobile stacking order is unchanged. The `≤520px` rules already target card
classes and need no change.

## Why no JavaScript

Forms grow when edit mode toggles (the `.save-banner` switches from
`display:none` to `flex`). CSS Grid recalculates row height automatically, so
the paired Profile card re-stretches without any JS height-sync.

## Testing

1. `php artisan view:clear`.
2. Desktop (≥769px): each Profile card height equals its paired form; content
   centred; no empty gradient gap. Toggle "Edit Profile" — form grows, Profile
   card grows with it.
3. ≤768px and ≤520px: confirm stacking order and card styling unchanged.

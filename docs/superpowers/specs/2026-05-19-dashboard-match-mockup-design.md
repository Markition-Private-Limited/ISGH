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

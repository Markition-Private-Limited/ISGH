# Checkomatic Parity on Change-Level Form

**Date:** 2026-06-10
**Status:** Approved for implementation

## Goal

Existing members can already change their membership level from the member portal. This spec covers two changes to that flow:

1. Restrict the offered levels to exactly: Checkomatic, Individual, Lifetime Family, Lifetime Individual.
2. When a member selects Checkomatic, replicate the same UX from the public signup flow: a donation-amount field with live feedback, a recurring-billing warning, and a spouse-disclaimer modal.

---

## Scope

### Not in scope
- Donation-type field (explicitly excluded).
- Any changes to the renewal flow.
- Any changes to the public signup page.

---

## Available Levels

The change-level dropdown shows exactly **4 entries**, minus the member's current level:

| Dropdown label        | Internal slug(s)      |
|-----------------------|-----------------------|
| Checkomatic           | `checkomatic_individual` (always) |
| Individual            | `individual`          |
| Lifetime Family       | `lifetime_family`     |
| Lifetime Individual   | `lifetime_individual` |

"Family Membership" (`family`) and "Flat Membership" (`flat`) are no longer offered as change targets. `checkomatic_family` is never submitted — Checkomatic always resolves to `checkomatic_individual` regardless of whether a spouse is added.

### Backend change — `LevelChangeService::availableLevels()`

Replace the current "all slugs minus current and flat" logic with an explicit allowlist:

```
ALLOWED = ['checkomatic_individual', 'individual', 'lifetime_family', 'lifetime_individual']
```

Return entries from `ALLOWED` minus the member's current slug. The `checkomatic_individual` entry is labelled "Checkomatic" in the response.

### Backend change — `LevelChangeService::charge()`

Add `checkomatic_family` to the server-side reject list (alongside `flat`), since it is no longer a valid change target.

---

## Checkomatic UX (Screen 1 — Pick a Level)

When the member selects "Checkomatic" the following elements appear below the dropdown.

### Amount field

Replaces the current amber box. Behaviour matches signup exactly:

- Minimum: **$10/mo** (not $20 — matches `getCheckomaticMinimum()` in signup).
- Input: number, `min="10"`, `step="1"`, default value `10`.
- Live feedback note below input: `"You will be charged $X.XX/month"` — updates on every keystroke.
- Error state: if value < 10, show `"Minimum monthly amount is $10.00."` and disable Continue.
- The Continue button (Screen 1 → next) stays disabled until the amount is valid (≥ 10).

### Recurring-billing warning

Shown directly below the amount field whenever Checkomatic is selected:

> "To qualify as a voting member for the current year, a minimum membership contribution of $20/person must be completed by June 30."

Styled like the signup `checkomatic-warning` block (amber/yellow tones). Always visible while Checkomatic is selected.

### Spouse disclaimer modal

Triggered when the user clicks "Add Spouse" on **Screen 2 (family screen)** while the selected level is Checkomatic. Blocks navigation into the spouse form until dismissed.

Content:
> **Checkomatic Reminder**
> Before adding a spouse, please review the payment reminder below.
> "Checkomatic dues totaling $20 per member must be paid by June 30th, [current year]."
> [Got It] button

After "Got It", the family screen proceeds normally. The disclaimer is a simple overlay modal using the existing `.confirm-overlay` / `.confirm-box` CSS already present in the app.

---

## Family / Spouse Screen (Screen 2)

Checkomatic **does show Screen 2** with an optional "Add Spouse" button. The disclaimer modal fires before the spouse form is revealed. The type sent to the server is always `checkomatic_individual` regardless of whether a spouse is added.

Because `MembershipTypes::includesFamily('checkomatic_individual')` currently returns `false`, the modal JS needs a special case: treat Checkomatic as family-screen-eligible. The Screen 2 heading reads "Add Spouse (Optional)" and the sub-text matches the signup Checkomatic spouse context.

The spouse block uses the existing `lvlFamilyTemplate` structure. Only one spouse block is allowed (no "+ Add Member" button for Checkomatic — it's spouse-only, same as `checkomatic_family` in the current logic).

---

## Review & Payment Screens (Screens 3–4)

No structural changes. The amount displayed uses the member's entered monthly amount (e.g. `$25.00/mo`).

---

## Data Flow

```
Member selects "Checkomatic"
  → enters monthly amount (min $10)
  → optionally adds spouse (disclaimer shown first)
  → review screen shows amount
  → pay screen charges amount
  → POST /member-portal/change-level with:
      target_type: "checkomatic_individual"
      monthly_amount: <entered float>
      family_members: [...] (may be non-empty, family slug never sent)
```

---

## Files Changed

| File | Change |
|------|--------|
| `app/Services/LevelChangeService.php` | `availableLevels()` uses explicit allowlist; `charge()` rejects `checkomatic_family` |
| `resources/views/member-portal/partials/level-modal.blade.php` | Amount field min→10, live note, warning block, spouse disclaimer modal, Screen 1 validation update |

No migration needed. No new routes. No changes to `ProcessLevelChange`, `LevelChange` model, or WA integration.

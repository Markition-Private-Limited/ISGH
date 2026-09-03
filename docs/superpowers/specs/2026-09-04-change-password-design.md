# Change Password — Staff Portal Design

**Date:** 2026-09-04
**Status:** Approved

## Summary

Add a "Change Password" modal to the Staff Portal so any authenticated admin user can update their own password at any time. This is a voluntary, self-service action — not a forced first-login flow.

## Scope

All authenticated Staff Portal users (Executive Board, Zone Directors, Associate Directors). No role restrictions.

## UX

- A "Change Password" item is added to the topbar role dropdown in `layouts/app.blade.php`, above the existing "Sign Out" item.
- Clicking it opens a modal dialog (no page navigation).
- The modal contains three fields: Current Password, New Password, Confirm New Password.
- On success: password updated, modal closes, page reloads with a `success` flash.
- On validation failure: the modal re-opens with inline field-level errors (errors stored in session, JS re-opens modal on page load if errors present).

## Validation Rules

| Field | Rule |
|---|---|
| current_password | required; must match the user's stored hash via `Hash::check()` |
| new_password | required, min:8, confirmed |
| new_password_confirmation | must match new_password |

Current password mismatch returns a field-level error on `current_password` (not a generic message).

## Architecture

### Route
`POST /admin/change-password` added inside the existing `auth` + `active.user` middleware group in `routes/web.php`. Named `portal.change-password`.

### Controller
`PortalController::changePassword(Request $request)`:
1. Validate all three fields.
2. `Hash::check($request->current_password, Auth::user()->password)` — abort with field error if wrong.
3. `Auth::user()->update(['password' => $request->new_password])` (model casts to hashed automatically).
4. Return `redirect()->back()->with('success', 'Password updated successfully.')`.

### Layout (`layouts/app.blade.php`)
- Add "Change Password" `<a>` item to the role dropdown menu above "Sign Out".
- Inject modal HTML once (always rendered, hidden by default).
- Small inline `<script>` block handles open/close; if session has `errors` for password fields, auto-open the modal on page load.

## Files Changed

| File | Change |
|---|---|
| `routes/web.php` | Add `POST /admin/change-password` route |
| `app/Http/Controllers/PortalController.php` | Add `changePassword()` method |
| `resources/views/layouts/app.blade.php` | Add dropdown item + modal + JS |

## Out of Scope

- Forced password change on first login (`must_change_password` flag — separate feature).
- Email notification on password change.
- Password strength meter.

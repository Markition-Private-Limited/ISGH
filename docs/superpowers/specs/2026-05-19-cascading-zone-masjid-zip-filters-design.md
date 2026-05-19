# Cascading Zone → Masjid → ZIP Filters (Staff Portal Members Page)

**Date:** 2026-05-19
**Files affected:**
`app/Http/Controllers/PortalController.php`,
`resources/views/portal/members/index.blade.php`.

## Goal

On the staff portal members page (`portal/members/index.blade.php`), the Zone,
Masjid, and ZIP Code filter dropdowns must cascade:

- Selecting a **Zone** narrows the **Masjid** dropdown to that zone's masajid.
- Selecting a **Masjid** narrows the **ZIP Code** dropdown to that masjid's zips.

Today all three dropdowns list city-wide values independently; selecting a Zone
or Masjid does not narrow the dependent dropdowns. ZIP codes appear city-wide
instead of masjid-filtered.

## Existing data — no new data model needed

The Zone→Masjid→ZIP hierarchy already exists:

- `dashboard_centers` table — `zone_name` ("Southeast Zone") + `center_name`.
- `dashboard_center_zips` table — center → zip rows.
- `PortalController::getMembersFilterOptions()` already walks the scoped
  dashboard tree `$scoped['zones'] → centers → zips`, but flattens it into two
  independent flat lists (`$masjids`, `$zipCodes`).
- `PortalController` already has a zone-slug ↔ zone-name map
  (`north => "North Zone"`, `southeast => "Southeast Zone"`, etc.) around
  line 366.

So the relationship data is present; only the wiring is missing.

## Confirmed behaviour decisions

- **Zone auto-submits.** Selecting a Zone reloads the results filtered to that
  zone — consistent with how Masjid and ZIP already auto-submit.
- **Server-rendered full lists stay** as the no-JS fallback; JS narrows them.
  The current selection survives a reload.

## Architecture

No new AJAX endpoints. Because Zone auto-submits, the cascade is split between
server and client:

- **Zone → Masjid/ZIP narrowing happens server-side on reload.** After a Zone is
  chosen the page reloads with `?zone=...`; the controller renders the Masjid
  and ZIP dropdowns already scoped to that zone.
- **Masjid → ZIP narrowing happens client-side**, without a reload, until the
  ZIP itself is picked (which auto-submits).

## Changes

### 1. Controller — zone-scoped options + a JSON tree

`getMembersFilterOptions()` gains the selected zone slug as a parameter:

- When a zone is selected, `$masjids` and `$zipCodes` include only that zone's
  centers and zips (matched by mapping the zone slug to the dashboard
  `zone['name']` via the existing slug↔name map). When no zone is selected,
  behaviour is unchanged (full scoped lists).
- It also returns a **`$filterTree`**: `[ masjidName => [zip, zip, ...], ... ]`
  for the in-scope (zone-narrowed) centers, so client JS can narrow the ZIP
  dropdown when a Masjid is chosen.

`members()` passes the request's `zone` into the call and forwards
`$masjids`, `$zipCodes`, `$filterTree` to the view.

### 2. View — emit the tree, keep full server-rendered lists

- Masjid and ZIP `<option>`s render server-side as today (full no-JS fallback,
  now zone-narrowed when a zone is in the query string).
- Emit `$filterTree` as a JS object via `@json(...)` in the `@push('scripts')`
  block.

### 3. JS — the cascade

- **Zone change** → submit the form (auto-submit), as Masjid/ZIP already do.
  The reload re-renders the dependent dropdowns zone-scoped.
- **Masjid change** → rebuild the ZIP `<option>` list from
  `filterTree[masjidName]` (a zip in multiple masajid is naturally included
  under each); reset ZIP to "All"; refresh Select2; then the existing
  auto-submit fires.
- All cascade JS no-ops gracefully when a dropdown is absent — the Zone
  dropdown renders only for city-wide users (`@if isCityWide()`), Masjid only
  for non-center users (`@if !isCenterLevel()`). Guard every lookup with a
  null check.

## Edge cases

- **Zone/center-restricted users.** Zone-level users have no Zone dropdown and
  their zone is force-locked server-side; center-level users have no Masjid
  dropdown and their center is locked. The JS must not assume the elements
  exist.
- **A ZIP in multiple masajid** — handled; rebuilding ZIP options from
  `filterTree[masjid]` includes the zip under each masjid it belongs to.
- **"All Zones" / "All Masjid"** — selecting "All" restores the wider list.

## Testing

1. `php artisan view:clear`.
2. As a city-wide user: select a Zone → page reloads, Masjid + ZIP dropdowns
   show only that zone's values. Pick a Masjid → ZIP list narrows to that
   masjid's zips with no reload until ZIP is chosen. Switch to "All Zones" →
   lists restore.
3. Reload with `?zone=southeast` in the URL → dropdowns reflect it.
4. Confirm zone-level and center-level users (missing Zone / Masjid dropdown)
   load the page without JS errors.

## Out of scope

- The public membership form (`membership-types.blade.php`) — it has its own
  separate ZIP→center lookup and is not part of this change.
- The `admin/show.blade.php` view — the cascading dropdowns live on the staff
  portal members page, not there.

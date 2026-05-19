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

No new AJAX endpoints, and no client-side option rebuilding. Every filter
level auto-submits, and the narrowing is done **entirely server-side on
reload** — which keeps the cascade consistent across reloads and manual URL
edits, and avoids JS/Select2 timing issues:

- **Zone change** → page reloads with `?zone=...`; the controller renders the
  Masjid dropdown narrowed to that zone and the ZIP dropdown narrowed to that
  zone.
- **Masjid change** → page reloads with `?center=...`; the controller narrows
  the ZIP dropdown to that masjid's zips.
- Each level's JS handler clears its now-stale dependent selections before
  submitting, so a `center`/`zip` filter never outlives the zone/masjid it
  belonged to.

## Changes

### 1. Controller — zone- and masjid-scoped options

A `ZONE_SLUG_TO_NAME` class constant maps the dropdown slug (`southeast`) to the
dashboard zone name (`Southeast Zone`).

`getMembersFilterOptions()` gains two optional parameters — the selected zone
slug and the selected center value:

- Zone set → `$masjids` and `$zipCodes` include only that zone's centers/zips.
- Center set → `$zipCodes` is further limited to that one masjid's zips.
- Nothing set → behaviour is unchanged (full scoped lists).

`members()` passes the request's `zone` and `center` (post-authorisation, so
zone/center-locked users get their forced scope) into the call.

### 2. View — server-rendered dropdowns

Masjid and ZIP `<option>`s render server-side as today — full no-JS fallback,
now narrowed by whatever zone/center is in the query string. No JSON tree, no
client-side option building.

### 3. JS — auto-submit + stale-dependent clearing

Each filter level auto-submits and clears its stale dependents first:

- **Zone change** → clear Masjid + ZIP selections, then submit.
- **Masjid change** → clear ZIP selection, then submit.
- **ZIP change** → submit.

Clearing happens before `form.submit()` so the URL never carries a `center`
from a different zone or a `zip` from a different masjid. All cascade JS
no-ops when a dropdown is absent (zone/center-restricted users).

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

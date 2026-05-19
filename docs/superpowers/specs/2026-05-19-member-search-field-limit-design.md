# Field-Limited Member Search (Name + Email)

**Date:** 2026-05-19
**Files affected:**
`app/Services/WildApricotService.php` (`getMembersPage`),
`resources/views/portal/members/index.blade.php` (search placeholder text).

## Goal

The staff portal members search box must search **only Name and Email** —
removing the current matching on ID, phone, ZIP, and other fields.

## Current behaviour

The search box value is passed to WildApricot's built-in `simpleQuery`
parameter (`WildApricotService::getMembersPage`, ~line 1411). `simpleQuery` is
WA's own free-text search — the app cannot tell it which fields to match; WA
matches name, email, phone, company, and more. The placeholder reads
"Search by name, ID, email, phone or zip code".

## Decision

Search is limited to **Name and Email only**, filtered **server-side** via
WildApricot OData `$filter`. Address was considered but dropped: Street Address
is a WA custom FieldValue, and WA OData cannot filter on FieldValues (the
codebase already documents this for the ZIP filter — *"WA OData cannot filter
on FieldValues, so apply post-fetch"*). A post-fetch address filter can only
ever remove rows from a Name/Email result set, never find address-only
matches, so it would not deliver real address search. Name and Email are
top-level WA fields and filter correctly server-side across the full dataset.

## Changes

### 1. `getMembersPage` — replace `simpleQuery` with an OData filter

Remove the `simpleQuery` assignment. When a search term is present, add an
OData `$filter` clause OR-ing the three top-level name/email matches:

```
(substringof('TERM', FirstName)
 OR substringof('TERM', LastName)
 OR substringof('TERM', Email))
```

The term is escaped (`'` → `''`) before interpolation. This is added to
`$filterParts` alongside the existing `Member eq true`, status, zone/center,
and level clauses, so it ANDs with them correctly. Result: search matches only
Name and Email, properly paginated across the full dataset; phone/ID/company
matching is gone.

### 2. Placeholder text

Update the search input placeholder in `portal/members/index.blade.php` from
"Search by name, ID, email, phone or zip code" to "Search by name or email".

## Testing

1. `php artisan view:clear`.
2. Search a member's first name → matches; last name → matches; email
   substring → matches.
3. Search a phone number or member ID → no longer matches (confirms
   ID/phone removed).
4. Search with another filter active (status, zone) → both apply together.
5. Confirm pagination still works with a search term active.

## Out of scope

- Address search (WA custom-field OData limitation).
- A "Search in" field selector dropdown.
- The public membership form search.

# Member-Portal Shared Modals Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract the renewal and level-change modals out of `dashboard.blade.php` into two shared Blade partials, then include those partials on the profile page and wire its three buttons to open them.

**Architecture:** Each modal (its CSS, HTML, and self-contained JS IIFE) becomes one Blade partial. `dashboard.blade.php` has its inline modal blocks replaced by `@include`s — a behavior-preserving move. `profile.blade.php` gains the same two `@include`s, and its three buttons get the trigger CSS classes the modal JS already binds to. No controller, route, or business-logic changes.

**Tech Stack:** Laravel 11 (Blade, PHPUnit), vanilla JS, Stripe.js.

---

## File Structure

- **Create** `resources/views/member-portal/partials/renew-modal.blade.php` — the renewal modal: its CSS `<style>` block, the `@unless($isLifetime)`-gated modal HTML, the Stripe `<script src>` tag, and the renewal JS IIFE. Carries the shared base modal CSS.
- **Create** `resources/views/member-portal/partials/level-modal.blade.php` — the level-change modal: its `.lvl-*` CSS `<style>` block, the modal HTML, and the level-change JS IIFE.
- **Modify** `resources/views/member-portal/dashboard.blade.php` — remove the inline modal CSS, HTML, and JS; add two `@include`s.
- **Modify** `resources/views/member-portal/profile.blade.php` — add two `@include`s; add trigger classes to three buttons.
- **Modify** `tests/Feature/MemberPortalIntegrationTest.php` — modal-presence tests.

### Key facts about the source (verified against the current dashboard.blade.php)

- **Modal CSS** is one contiguous region of the `<style>` block: it starts at the line `/* ── Renewal modal ── */` (immediately followed by `.renew-overlay {`) and ends at the closing `}` of the `.lvl-family-empty { … }` rule, which is the LAST rule before `</style>`.
  - The renewal + shared-base subset runs from `/* ── Renewal modal ── */` through the `.renew-invoice { … }` rule (the rule right before the `/* ── Level-change modal (reuses .renew-* …) ── */` comment).
  - The level subset runs from `/* ── Level-change modal (reuses .renew-* …) ── */` through `.lvl-family-empty { … }`.
- **Renewal modal block** is one contiguous region in `<body>`: it starts at the line `{{-- ── Renewal modal ── --}}` and ends at the `@endunless` that matches the `@unless($isLifetime)` on the line right after that comment. This region contains, in order: the comment, `@unless($isLifetime)`, the `<div class="renew-overlay" id="renewModal" …>` … `</div>` HTML, a blank line, `<script src="https://js.stripe.com/v3/"></script>`, a blank line, the renewal `<script>` … `</script>` IIFE, and finally `@endunless`. (The `@unless` wraps the HTML, the Stripe tag, AND the JS — all of it.)
- **Level-change modal block** is one contiguous region in `<body>`: it starts at the line `{{-- ── Level-change modal ── --}}` and ends at the `</script>` that closes the level-change JS IIFE — which is the last `</script>` before `</body>`. It contains: the comment, the `<div class="renew-overlay" id="levelModal" …>` … `</div>` HTML, and the level-change `<script>` … `</script>` IIFE. It has NO `@unless` gate and NO Blade variables.
- The dashboard's `@php` block (just after `<body>`) computes `$isLifetime = stripos($profile->level, 'lifetime') !== false;` and `$isExpired = $profile->isExpired();` among others. Leave that block untouched.

---

## Task 1: Create the renewal modal partial

Move the renewal modal's CSS, HTML, Stripe tag, and JS out of the dashboard into a new partial. This task ONLY creates the partial (by moving content); Task 3 updates the dashboard to use it.

**Files:**
- Create: `resources/views/member-portal/partials/renew-modal.blade.php`
- Modify: `resources/views/member-portal/dashboard.blade.php` (content is moved out)

- [ ] **Step 1: Open `resources/views/member-portal/dashboard.blade.php` and locate the three source regions**

Identify by content (line numbers will have drifted — do not trust absolute numbers):
- **CSS-renew region:** from the line `/* ── Renewal modal ── */` through the closing `}` of the `.renew-invoice { … }` rule (the rule immediately before the comment `/* ── Level-change modal (reuses .renew-* overlay/card/button styles) ── */`).
- **HTML+JS-renew region:** from the line `{{-- ── Renewal modal ── --}}` through the `@endunless` that closes the `@unless($isLifetime)` directly below that comment. This region includes the modal HTML, the `<script src="https://js.stripe.com/v3/"></script>` tag, and the renewal `<script>` IIFE — all inside the `@unless`.

- [ ] **Step 2: Create the partial file with the moved content**

Create `resources/views/member-portal/partials/renew-modal.blade.php`. Its content, in this exact order:

1. This header comment and `@php` block, verbatim:

```blade
{{--
  Renewal modal — shared by the member dashboard and profile pages.
  Self-contained: carries its own CSS, HTML, and JS. The renewal JS IIFE
  early-returns when #renewModal is absent (lifetime members), so including
  this partial is always safe.
  Requires: $profile (App\Support\MemberProfile) in scope.
--}}
@php
  $isLifetime = stripos(($profile->level ?? ''), 'lifetime') !== false;
  $isExpired  = $profile->isExpired();
@endphp
<style>
```

2. Immediately after that `<style>` line: paste the **CSS-renew region** content from Step 1 (the lines from `/* ── Renewal modal ── */` through the `.renew-invoice` rule's closing `}`).

3. Then a closing `</style>` on its own line.

4. Then the **HTML+JS-renew region** content from Step 1 — paste it verbatim (the `{{-- ── Renewal modal ── --}}` comment, `@unless($isLifetime)`, the modal HTML, the Stripe `<script src>` tag, the renewal `<script>` IIFE, and `@endunless`).

The partial structure is therefore: header `@php` → `<style>` + renewal CSS + `</style>` → the comment + `@unless`-wrapped (HTML + Stripe tag + JS) + `@endunless`.

- [ ] **Step 3: Delete the moved content from the dashboard**

In `dashboard.blade.php`, delete BOTH source regions identified in Step 1:
- Delete the **CSS-renew region** (from `/* ── Renewal modal ── */` through the `.renew-invoice` rule's closing `}`).
- Delete the **HTML+JS-renew region** (from `{{-- ── Renewal modal ── --}}` through its `@endunless`).

Do not yet add the `@include` — that is Task 3. After this step the dashboard is temporarily missing the renewal modal; that is expected and Task 3 restores it.

- [ ] **Step 4: Verify the partial parses and the dashboard still compiles**

Run: `php artisan view:clear`
Then: `php artisan tinker --execute="echo view()->exists('member-portal.partials.renew-modal') ? 'EXISTS' : 'MISSING';"`
Expected: `EXISTS`.

Then confirm the dashboard view still compiles without the renewal block:
Run: `php artisan test --filter='MemberPortalIntegrationTest::test_dashboard_renders_member_data'`
Expected: PASS (the dashboard renders; it just has no renewal modal yet).

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/partials/renew-modal.blade.php resources/views/member-portal/dashboard.blade.php
git commit -m "refactor: extract renewal modal into shared partial"
```

---

## Task 2: Create the level-change modal partial

Move the level-change modal's CSS, HTML, and JS out of the dashboard into a new partial. This task ONLY creates the partial; Task 3 updates the dashboard to use it.

**Files:**
- Create: `resources/views/member-portal/partials/level-modal.blade.php`
- Modify: `resources/views/member-portal/dashboard.blade.php` (content is moved out)

- [ ] **Step 1: Locate the two source regions in `dashboard.blade.php`**

Identify by content:
- **CSS-level region:** from the line `/* ── Level-change modal (reuses .renew-* overlay/card/button styles) ── */` through the closing `}` of the `.lvl-family-empty { … }` rule (the last rule before `</style>`).
- **HTML+JS-level region:** from the line `{{-- ── Level-change modal ── --}}` through the `</script>` that closes the level-change JS IIFE (the last `</script>` before `</body>`). This region contains the comment, the `<div class="renew-overlay" id="levelModal" …>` … `</div>` HTML, and the level-change `<script>` IIFE. It has no `@unless` and no Blade variables.

- [ ] **Step 2: Create the partial file with the moved content**

Create `resources/views/member-portal/partials/level-modal.blade.php`. Its content, in this exact order:

1. This header comment, verbatim:

```blade
{{--
  Level-change modal — shared by the member dashboard and profile pages.
  Self-contained: carries its own CSS, HTML, and JS. The .lvl-* CSS depends
  on the shared base modal classes (.renew-overlay, .renew-modal, etc.),
  which are provided by the renew-modal partial — always include that partial
  alongside this one. The level-change JS IIFE early-returns when #levelModal
  is absent, so including this partial is safe.
--}}
<style>
```

2. Immediately after that `<style>` line: paste the **CSS-level region** content from Step 1.

3. Then a closing `</style>` on its own line.

4. Then the **HTML+JS-level region** content from Step 1 — paste it verbatim (the `{{-- ── Level-change modal ── --}}` comment, the modal HTML, and the level-change `<script>` IIFE).

- [ ] **Step 3: Delete the moved content from the dashboard**

In `dashboard.blade.php`, delete BOTH source regions from Step 1:
- Delete the **CSS-level region** (`/* ── Level-change modal … ── */` through `.lvl-family-empty`'s closing `}`). After this and Task 1's CSS deletion, the `<style>` block ends at whatever rule preceded `/* ── Renewal modal ── */`, immediately followed by `</style>`.
- Delete the **HTML+JS-level region** (`{{-- ── Level-change modal ── --}}` through the IIFE's closing `</script>`).

Do not yet add the `@include` — that is Task 3.

- [ ] **Step 4: Verify the partial parses and the dashboard still compiles**

Run: `php artisan view:clear`
Then: `php artisan tinker --execute="echo view()->exists('member-portal.partials.level-modal') ? 'EXISTS' : 'MISSING';"`
Expected: `EXISTS`.

Then: `php artisan test --filter='MemberPortalIntegrationTest::test_dashboard_renders_member_data'`
Expected: PASS (the dashboard renders; both modals are temporarily absent until Task 3).

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/partials/level-modal.blade.php resources/views/member-portal/dashboard.blade.php
git commit -m "refactor: extract level-change modal into shared partial"
```

---

## Task 3: Wire the partials back into the dashboard

Add the two `@include`s to the dashboard so it renders the modals via the partials — restoring its original behavior.

**Files:**
- Modify: `resources/views/member-portal/dashboard.blade.php`

- [ ] **Step 1: Add the two includes**

In `dashboard.blade.php`, find the end of `<body>` — the `</body>` tag near the end of the file. Immediately BEFORE `</body>` (after the last existing content/script), add these two lines:

```blade
@include('member-portal.partials.renew-modal')
@include('member-portal.partials.level-modal')
```

(Order matters: `renew-modal` first, because it carries the shared base CSS that `level-modal`'s `.lvl-*` rules depend on. CSS later in the document does not need to come after rules it depends on — CSS cascade is not order-sensitive for unrelated selectors — but keeping renew first matches the partials' documented contract and is the safe, readable choice.)

- [ ] **Step 2: Verify the dashboard renders both modals**

Run: `php artisan view:clear`
Then run this assertion:
`php artisan test --filter='MemberPortalIntegrationTest::test_dashboard_renders_member_data'`
Expected: PASS.

Then confirm both modal IDs are present in the rendered dashboard. Run:
`php artisan tinker --execute="\$v = view('member-portal.dashboard', ['profile' => new App\Support\MemberProfile(['contact' => ['Id' => 1, 'FirstName' => 'Test', 'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => []]]), 'email' => 'x@y.com'])->render(); echo (str_contains(\$v, 'id=\"renewModal\"') ? 'RENEW-OK ' : 'RENEW-MISSING '); echo (str_contains(\$v, 'id=\"levelModal\"') ? 'LEVEL-OK' : 'LEVEL-MISSING');"`
Expected output: `RENEW-OK LEVEL-OK`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/member-portal/dashboard.blade.php
git commit -m "refactor: dashboard renders modals via shared partials"
```

---

## Task 4: Include the modals on the profile page and wire its buttons

Add the two partials to `profile.blade.php` and give its three buttons the trigger classes the modal JS binds to.

**Files:**
- Modify: `resources/views/member-portal/profile.blade.php`

- [ ] **Step 1: Add the two includes**

In `resources/views/member-portal/profile.blade.php`, find the `</body>` tag near the end of the file. Immediately BEFORE `</body>` (after the page's existing content and `<script>` blocks), add:

```blade
@include('member-portal.partials.renew-modal')
@include('member-portal.partials.level-modal')
```

- [ ] **Step 2: Wire the "Renew Membership" quicklink**

Find the "Renew Membership" quicklink — an `<a href="#" class="ql-item">` whose visible text is `Renew Membership` (it contains an SVG with `<polyline points="23 4 23 10 17 10"/>`). Change its opening tag from:

```blade
<a href="#" class="ql-item">
```

to:

```blade
<a href="#" class="ql-item ql-renew-link">
```

- [ ] **Step 3: Wire the "Change Level" quicklink**

Find the "Change Level" quicklink — an `<a href="#" class="ql-item">` whose visible text is `Change Level` (it contains an SVG with three `<line>` elements forming a bar chart: `<line x1="18" y1="20" x2="18" y2="10"/>` etc.). Change its opening tag from:

```blade
<a href="#" class="ql-item">
```

to:

```blade
<a href="#" class="ql-item ql-change-level">
```

- [ ] **Step 4: Wire the "Change Membership Level" button**

Find the "Change Membership Level" button — a `<button type="button" class="btn-change">` whose visible text is `Change Membership Level`. Change its opening tag from:

```blade
<button type="button" class="btn-change">
```

to:

```blade
<button type="button" class="btn-change ql-change-level">
```

(The level-change modal JS binds open-triggers via `document.querySelectorAll('.ql-change-level')`, and the renewal JS via `'.btn-renew, .btn-renew-mobile, .ql-renew-link'`. Adding these classes is the entire wiring — no new JavaScript.)

- [ ] **Step 5: Verify the profile page renders both modals**

Run: `php artisan view:clear`
Then: `php artisan test --filter='MemberPortalIntegrationTest::test_profile_renders_member_data'`
Expected: PASS.

Then confirm both modal IDs render on the profile page:
`php artisan tinker --execute="\$v = view('member-portal.profile', ['profile' => new App\Support\MemberProfile(['contact' => ['Id' => 1, 'FirstName' => 'Test', 'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => []]]), 'email' => 'x@y.com'])->render(); echo (str_contains(\$v, 'id=\"renewModal\"') ? 'RENEW-OK ' : 'RENEW-MISSING '); echo (str_contains(\$v, 'id=\"levelModal\"') ? 'LEVEL-OK' : 'LEVEL-MISSING');"`
Expected output: `RENEW-OK LEVEL-OK`.

- [ ] **Step 6: Commit**

```bash
git add resources/views/member-portal/profile.blade.php
git commit -m "feat: profile page opens renewal and level-change modals"
```

---

## Task 5: Feature tests for modal presence

**Files:**
- Modify: `tests/Feature/MemberPortalIntegrationTest.php`

- [ ] **Step 1: Write the tests**

Add these methods to `tests/Feature/MemberPortalIntegrationTest.php`, immediately before the class's final closing `}`:

```php
    public function test_profile_page_includes_renewal_and_level_modals(): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => [], 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/profile')
            ->assertOk()
            ->assertSee('id="renewModal"', false)
            ->assertSee('id="levelModal"', false)
            ->assertSee('ql-renew-link', false)
            ->assertSee('ql-change-level', false);
    }

    public function test_dashboard_still_includes_both_modals_after_extraction(): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => [], 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/dashboard')
            ->assertOk()
            ->assertSee('id="renewModal"', false)
            ->assertSee('id="levelModal"', false);
    }

    public function test_lifetime_member_profile_omits_renewal_modal(): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Lifetime Membership (Individual)'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => [], 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/profile')
            ->assertOk()
            ->assertSee('id="levelModal"', false)
            ->assertDontSee('id="renewModal"', false);
    }
```

`assertSee(..., false)` / `assertDontSee(..., false)` — the `false` second argument disables HTML-escaping of the needle so it matches the raw attribute string in the response.

- [ ] **Step 2: Run the tests**

Run: `php artisan view:clear`
Then: `php artisan test --filter='MemberPortalIntegrationTest'`
Expected: ALL tests in the class PASS (the existing suite plus these 3 new ones).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/MemberPortalIntegrationTest.php
git commit -m "test: cover shared renewal/level modals on profile and dashboard"
```

---

## Task 6: Full verification

- [ ] **Step 1: Clear caches and run the member-portal suites**

Run: `php artisan view:clear`
Then: `php artisan test tests/Unit/MemberProfileTest.php tests/Feature/MemberPortalIntegrationTest.php tests/Feature/MemberPortalServiceTest.php`
Expected: PASS — all member-portal tests green.

- [ ] **Step 2: Confirm no modal markup remains inline in the dashboard**

Run: `grep -nE 'id="renewModal"|id="levelModal"|Renewal modal module|Level-change modal module' resources/views/member-portal/dashboard.blade.php`
Expected: NO output — the dashboard no longer contains inline modal HTML or the modal JS IIFE comments; both come from the partials now.

- [ ] **Step 3: Confirm both partials exist and are the only home of the modal markup**

Run: `grep -rln 'id="renewModal"' resources/views/member-portal/`
Expected: exactly one file — `resources/views/member-portal/partials/renew-modal.blade.php`.

Run: `grep -rln 'id="levelModal"' resources/views/member-portal/`
Expected: exactly one file — `resources/views/member-portal/partials/level-modal.blade.php`.

- [ ] **Step 4: Final commit (only if uncommitted changes remain)**

```bash
git status
# if clean, nothing to do; otherwise:
git add -A && git commit -m "chore: finalize shared member-portal modals"
```

---

## Self-Review Notes

- **Spec coverage:** renew-modal partial with CSS+HTML+Stripe+JS, `@unless` gate, `@php` header deriving `$isLifetime`/`$isExpired` (Task 1) ✓; level-modal partial with `.lvl-*` CSS + HTML + JS, no gate (Task 2) ✓; dashboard inline modals removed and replaced by `@include`s (Tasks 1–3) ✓; profile page includes both partials (Task 4) ✓; three profile buttons wired via `ql-renew-link` / `ql-change-level` trigger classes (Task 4) ✓; lifetime member sees level modal but not renewal modal (Task 5 test) ✓; no controller/route/logic changes ✓; tests for modal presence on profile + dashboard + lifetime case (Task 5) ✓.
- **Type/name consistency:** the trigger classes (`ql-renew-link`, `ql-change-level`, `btn-renew`, `btn-renew-mobile`) match exactly what the modal JS IIFEs query. Partial names (`member-portal.partials.renew-modal`, `member-portal.partials.level-modal`) are consistent across Tasks 1–4. The `@php` header in renew-modal uses `$profile->level` / `$profile->isExpired()` — both real members of `App\Support\MemberProfile`.
- **Placeholder scan:** the two modal blocks are moved (not retyped) because they are ~1100 lines; the plan specifies exact content-based delimiters for each region rather than pasting — this is a deliberate move-by-delimiter, not a placeholder. Every other step shows complete content.
- **Note for the engineer:** `dashboard.blade.php` has active parallel work (the modal feature originated there). The extraction is a behavior-preserving move; Task 3 Step 2 explicitly re-verifies both modal IDs render on the dashboard so any mistake is caught immediately.

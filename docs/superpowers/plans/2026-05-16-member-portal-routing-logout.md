# Member-Portal Routing & Logout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire every sidebar nav item, mobile bottom-nav item, and quicklink across all 8 member-portal pages to its real route, and add a working logout button to the sidebar.

**Architecture:** Consolidate the duplicated sidebar/bottom-nav markup onto two shared Blade partials (`partials/sidebar.blade.php`, new `partials/bottom-nav.blade.php`), each parameterized by `$active` and `$mode` (`spa` for the dashboard, `links` for all other pages). Every member-portal view `@include`s both partials. All routes already exist; no backend changes. Logout is a CSRF-protected POST form in the sidebar hitting the existing `member-portal.logout` route.

**Tech Stack:** Laravel 11 (Blade, PHPUnit), no JS framework.

---

## File Structure

- **Modify** `resources/views/member-portal/partials/sidebar.blade.php` — wire all 8 nav items to routes, add the logout form.
- **Create** `resources/views/member-portal/partials/bottom-nav.blade.php` — new shared mobile bottom-nav partial.
- **Modify** `resources/views/member-portal/dashboard.blade.php` — replace inline bottom-nav with partial, route quicklinks, add `.nav-logout` CSS.
- **Modify** `resources/views/member-portal/payments.blade.php` — add bottom-nav partial + its CSS, add `.nav-logout` CSS.
- **Modify** `resources/views/member-portal/profile.blade.php` — replace inline sidebar + bottom-nav with partials, route quicklinks, add `.nav-logout` CSS.
- **Modify** `resources/views/member-portal/isgh-records.blade.php` — replace inline sidebar + bottom-nav with partials, add `.nav-logout` CSS.
- **Modify** `resources/views/member-portal/newsletter.blade.php` — same as isgh-records.
- **Modify** `resources/views/member-portal/financial-report.blade.php` — same as isgh-records.
- **Modify** `resources/views/member-portal/updates.blade.php` — same as isgh-records.
- **Modify** `resources/views/member-portal/nominees-training.blade.php` — same as isgh-records.
- **Modify** `tests/Feature/MemberPortalIntegrationTest.php` — route-renders + logout tests.

### Shared interface — partial parameters

Both partials accept the SAME two parameters, used identically:

- `$active` — one of: `dashboard`, `profile`, `payments`, `records`, `newsletter`, `financial-report`, `updates`, `nominees-training`. The matching item gets the `active` class. An unknown/omitted value highlights nothing.
- `$mode` — `spa` or `links`. Defaults to `links` when omitted. In `spa` mode the Dashboard and Profile items use `onclick="showPage(...)"`; in `links` mode every item is a real `href`.

The `$active` keys each map to a route: `dashboard`→`member-portal.dashboard`, `profile`→`member-portal.profile`, `payments`→`member-portal.payments`, `records`→`member-portal.records`, `newsletter`→`member-portal.newsletter`, `financial-report`→`member-portal.financial-report`, `updates`→`member-portal.updates`, `nominees-training`→`member-portal.nominees-training`.

---

## Task 1: Sidebar partial — wire 8 routes + add logout

**Files:**
- Modify: `resources/views/member-portal/partials/sidebar.blade.php`

- [ ] **Step 1: Replace the entire partial file**

Overwrite `resources/views/member-portal/partials/sidebar.blade.php` with this EXACT content:

```blade
{{--
  Member-portal sidebar.
  @param string $active  dashboard | profile | payments | records | newsletter
                         | financial-report | updates | nominees-training
  @param string $mode    'spa'   — Dashboard/Profile switch via showPage() (dashboard view)
                         'links' — every item is a real href (other pages)
--}}
@php $mode = $mode ?? 'links'; @endphp
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-left">
      <div class="brand-logo">
        <img src="{{ asset('images/logo.png') }}" alt="ISGH" onerror="this.style.display='none'">
      </div>
      <span class="brand-name">ISGH</span>
    </div>
    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
  </div>

  <nav class="sidebar-nav">
    {{-- Dashboard --}}
    @if($mode === 'spa')
      <a href="#" class="nav-item {{ $active === 'dashboard' ? 'active' : '' }}" data-page-link="dashboard" onclick="event.preventDefault(); showPage('dashboard')">
    @else
      <a href="{{ route('member-portal.dashboard') }}" class="nav-item {{ $active === 'dashboard' ? 'active' : '' }}">
    @endif
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>

    {{-- Profile --}}
    @if($mode === 'spa')
      <a href="#" class="nav-item {{ $active === 'profile' ? 'active' : '' }}" data-page-link="profile" onclick="event.preventDefault(); showPage('profile')">
    @else
      <a href="{{ route('member-portal.profile') }}" class="nav-item {{ $active === 'profile' ? 'active' : '' }}">
    @endif
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      Profile
    </a>

    {{-- Payment & Invoice --}}
    <a href="{{ route('member-portal.payments') }}" class="nav-item {{ $active === 'payments' ? 'active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/>
      </svg>
      Payment &amp; Invoice
    </a>

    {{-- ISGH Records --}}
    <a href="{{ route('member-portal.records') }}" class="nav-item {{ $active === 'records' ? 'active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/>
      </svg>
      ISGH Records
    </a>

    {{-- Isgh Newsletter --}}
    <a href="{{ route('member-portal.newsletter') }}" class="nav-item {{ $active === 'newsletter' ? 'active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/>
      </svg>
      Isgh Newsletter
    </a>

    {{-- Financial Report --}}
    <a href="{{ route('member-portal.financial-report') }}" class="nav-item {{ $active === 'financial-report' ? 'active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 14l2 2 4-4"/>
      </svg>
      Financial Report
    </a>

    {{-- Updates --}}
    <a href="{{ route('member-portal.updates') }}" class="nav-item {{ $active === 'updates' ? 'active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      Updates
    </a>

    {{-- Nominees Training & Orientation --}}
    <a href="{{ route('member-portal.nominees-training') }}" class="nav-item {{ $active === 'nominees-training' ? 'active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      Nominees Training &amp; Orientation
    </a>

    {{-- Logout --}}
    <form method="POST" action="{{ route('member-portal.logout') }}" class="nav-logout-form">
      @csrf
      <button type="submit" class="nav-item nav-logout">
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Logout
      </button>
    </form>
  </nav>
</aside>
```

- [ ] **Step 2: Verify the partial parses**

Run: `php artisan view:clear`
Then: `php artisan tinker --execute="echo view()->exists('member-portal.partials.sidebar') ? 'EXISTS' : 'MISSING';"`
Expected output: `EXISTS`

- [ ] **Step 3: Commit**

```bash
git add resources/views/member-portal/partials/sidebar.blade.php
git commit -m "feat: wire all 8 sidebar routes and add logout button"
```

---

## Task 2: New shared bottom-nav partial

**Files:**
- Create: `resources/views/member-portal/partials/bottom-nav.blade.php`

- [ ] **Step 1: Create the partial**

Create `resources/views/member-portal/partials/bottom-nav.blade.php` with this EXACT content:

```blade
{{--
  Member-portal mobile bottom navigation.
  @param string $active  dashboard | profile | payments | records | financial-report
  @param string $mode    'spa'   — Dashboard/Profile switch via showPage() (dashboard view)
                         'links' — every item is a real href (other pages)
  Note: only 5 items fit the bottom bar. Pages whose $active is not one of the
  five (newsletter, updates, nominees-training) simply highlight nothing here.
--}}
@php $mode = $mode ?? 'links'; @endphp
<nav class="bottom-nav" aria-label="Bottom navigation">
  {{-- Dashboard --}}
  @if($mode === 'spa')
    <a href="#" class="bn-item {{ $active === 'dashboard' ? 'active' : '' }}" data-page-link="dashboard" onclick="event.preventDefault(); showPage('dashboard')">
  @else
    <a href="{{ route('member-portal.dashboard') }}" class="bn-item {{ $active === 'dashboard' ? 'active' : '' }}">
  @endif
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
    </span>
    Dashboard
  </a>

  {{-- Profile --}}
  @if($mode === 'spa')
    <a href="#" class="bn-item {{ $active === 'profile' ? 'active' : '' }}" data-page-link="profile" onclick="event.preventDefault(); showPage('profile')">
  @else
    <a href="{{ route('member-portal.profile') }}" class="bn-item {{ $active === 'profile' ? 'active' : '' }}">
  @endif
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </span>
    Profile
  </a>

  {{-- Payments --}}
  <a href="{{ route('member-portal.payments') }}" class="bn-item {{ $active === 'payments' ? 'active' : '' }}">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/>
      </svg>
    </span>
    Payments
  </a>

  {{-- Records --}}
  <a href="{{ route('member-portal.records') }}" class="bn-item {{ $active === 'records' ? 'active' : '' }}">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
      </svg>
    </span>
    Records
  </a>

  {{-- Reports (Financial Report) --}}
  <a href="{{ route('member-portal.financial-report') }}" class="bn-item {{ $active === 'financial-report' ? 'active' : '' }}">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 14l2 2 4-4"/>
      </svg>
    </span>
    Reports
  </a>
</nav>
```

- [ ] **Step 2: Verify the partial parses**

Run: `php artisan tinker --execute="echo view()->exists('member-portal.partials.bottom-nav') ? 'EXISTS' : 'MISSING';"`
Expected output: `EXISTS`

- [ ] **Step 3: Commit**

```bash
git add resources/views/member-portal/partials/bottom-nav.blade.php
git commit -m "feat: add shared member-portal bottom-nav partial"
```

---

## Task 3: Dashboard — bottom-nav partial, quicklinks, logout CSS

**Files:**
- Modify: `resources/views/member-portal/dashboard.blade.php`

- [ ] **Step 1: Add the `.nav-logout` CSS**

In `resources/views/member-portal/dashboard.blade.php`, find the CSS rule for `.nav-item.active:hover` inside the `<style>` block (search for `.nav-item.active:hover`). Immediately AFTER that rule's closing `}`, add these rules:

```css
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Replace the inline bottom-nav with the partial**

In the same file, find the inline mobile bottom-nav. It is a block that starts with the comment `{{-- ── Bottom Nav (mobile only) ── --}}` followed by `<nav class="bottom-nav" aria-label="Bottom navigation">` and ends with the matching `</nav>`. Delete that entire block (the comment line, the `<nav>`, all five `<a class="bn-item">` items, and the closing `</nav>`) and replace it with:

```blade
{{-- ── Bottom Nav (mobile only) ── --}}
@include('member-portal.partials.bottom-nav', ['active' => 'dashboard', 'mode' => 'spa'])
```

- [ ] **Step 3: Route the navigation quicklinks**

In the same file, find the Quick Links block (search for `{{-- Quick Links --}}`). Inside `<div class="ql-list">` there are five `<a>` items. Change the `href` of these THREE navigation items only (leave the `ql-renew-link` and `ql-change-level` items completely untouched — they open modals):

- The "View Invoices" item: change its opening tag `<a href="#" class="ql-item">` to `<a href="{{ route('member-portal.payments') }}" class="ql-item">`.
- The "Payment History" item: change its opening tag `<a href="#" class="ql-item">` to `<a href="{{ route('member-portal.payments') }}" class="ql-item">`.
- The "Update Profile" item: change its opening tag `<a href="#" class="ql-item">` to `<a href="#" class="ql-item" onclick="event.preventDefault(); showPage('profile')">`.

To disambiguate: the "View Invoices", "Payment History", and "Update Profile" items each have the bare opening tag `<a href="#" class="ql-item">` (no extra class). Identify each by the visible text label inside it. Do NOT touch `<a href="#" class="ql-item ql-renew-link">` or `<a href="#" class="ql-item ql-change-level">`.

- [ ] **Step 4: Verify the dashboard renders**

Run: `php artisan view:clear`
Then: `php artisan test --filter='MemberPortalIntegrationTest::test_dashboard_renders_member_data'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/dashboard.blade.php
git commit -m "feat: dashboard uses bottom-nav partial, routes quicklinks"
```

---

## Task 4: Payments page — add bottom-nav partial + CSS

**Files:**
- Modify: `resources/views/member-portal/payments.blade.php`

- [ ] **Step 1: Add bottom-nav CSS**

`payments.blade.php` has no bottom-nav styles yet. In its `<style>` block, find the rule for `.sidebar-overlay.open` (search for `.sidebar-overlay.open`). Immediately AFTER that rule's closing `}`, add the bottom-nav styles and the logout styles:

```css
    .bottom-nav {
      display: none;
      position: fixed; bottom: 0; left: 0; right: 0; z-index: 45;
      background: var(--surface); border-top: 1px solid var(--border);
      padding: 8px 6px; justify-content: space-around;
    }
    .bn-item {
      display: flex; flex-direction: column; align-items: center; gap: 3px;
      font-size: 11px; font-weight: 500; color: var(--text-muted);
      flex: 1; padding: 4px 2px;
    }
    .bn-icon svg { width: 20px; height: 20px; stroke-width: 1.8; }
    .bn-item.active { color: var(--green); }
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Show the bottom-nav on mobile**

In the same `<style>` block, find the `@media (max-width:768px)` block (search for `@media (max-width:768px)`). Inside that media query's braces, add this line (anywhere among the existing rules in that block):

```css
      .bottom-nav { display: flex; }
```

- [ ] **Step 3: Include the bottom-nav partial**

In the body of `payments.blade.php`, find the closing `</div>` of `<div class="app">`. The structure near the end of the body is the `</div>` closing `.app`, then `<script>`. Immediately AFTER the `</div>` that closes `<div class="app">` and BEFORE the `<script>` tag, add:

```blade
@include('member-portal.partials.bottom-nav', ['active' => 'payments', 'mode' => 'links'])
```

To locate it precisely: the `.app` div is opened with `<div class="app">` near the top of the body. Its matching close is the last `</div>` before the page's first `<script>`. Add the include on its own line between them.

- [ ] **Step 4: Verify the payments page renders**

Run: `php artisan view:clear`
Then: `php artisan test --filter='MemberPortalIntegrationTest::test_payments_page_renders_invoice_history'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/payments.blade.php
git commit -m "feat: payments page gets shared bottom-nav"
```

---

## Task 5: Profile page — both partials, quicklinks, logout CSS

**Files:**
- Modify: `resources/views/member-portal/profile.blade.php`

- [ ] **Step 1: Add the `.nav-logout` CSS**

In `resources/views/member-portal/profile.blade.php`, find the CSS rule `.nav-item.active:hover` in the `<style>` block. Immediately AFTER its closing `}`, add:

```css
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Replace the inline sidebar with the partial**

In the same file, find the inline sidebar: it starts with `<aside class="sidebar" id="sidebar">` and ends with the matching `</aside>`. The line immediately after that `</aside>` is `<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>`.

Delete the entire `<aside class="sidebar" id="sidebar"> … </aside>` block and replace it with:

```blade
  @include('member-portal.partials.sidebar', ['active' => 'profile', 'mode' => 'links'])
```

Do NOT delete the `<div class="sidebar-overlay" …>` line that follows — leave it in place. There is exactly one `id="sidebar"` aside; its `</aside>` is the one directly before `sidebar-overlay`.

- [ ] **Step 3: Replace the inline bottom-nav with the partial**

In the same file, find the inline bottom-nav block: the comment `{{-- ── Bottom Nav (mobile) ── --}}` (or similar) if present, then `<nav class="bottom-nav" aria-label="Bottom navigation">`, all its `<a class="bn-item">` children, and the closing `</nav>`. Delete that whole `<nav class="bottom-nav"> … </nav>` block and replace it with:

```blade
@include('member-portal.partials.bottom-nav', ['active' => 'profile', 'mode' => 'links'])
```

- [ ] **Step 4: Route the navigation quicklinks**

Find the Quick Links block (search for `{{-- Quick Links --}}`). Inside `<div class="ql-list">` there are four `<a href="#" class="ql-item">` items: "Renew Membership", "View Invoices", "Payment History", "Change Level". Change the `href` of the TWO navigation items only:

- The "View Invoices" item: change `<a href="#" class="ql-item">` to `<a href="{{ route('member-portal.payments') }}" class="ql-item">`.
- The "Payment History" item: change `<a href="#" class="ql-item">` to `<a href="{{ route('member-portal.payments') }}" class="ql-item">`.

Leave "Renew Membership" and "Change Level" as `<a href="#" class="ql-item">` — `profile.blade.php` is a standalone page with no renewal/level-change modal, so they stay as inert placeholders (out of scope to wire). Identify each item by its visible text label.

- [ ] **Step 5: Verify the profile page renders**

Run: `php artisan view:clear`
Then: `php artisan test --filter='MemberPortalIntegrationTest::test_profile_renders_member_data'`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/member-portal/profile.blade.php
git commit -m "feat: profile page uses shared sidebar + bottom-nav partials"
```

---

## Task 6: ISGH Records page — both partials, logout CSS

**Files:**
- Modify: `resources/views/member-portal/isgh-records.blade.php`

- [ ] **Step 1: Add the `.nav-logout` CSS**

In `resources/views/member-portal/isgh-records.blade.php`, find the CSS rule `.nav-item.active:hover` in the `<style>` block. Immediately AFTER its closing `}`, add:

```css
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Replace the inline sidebar with the partial**

Find the inline sidebar: `<aside class="sidebar" id="sidebar">` through its matching `</aside>` (the line after it is `<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>`). Delete the whole `<aside>…</aside>` block and replace it with:

```blade
  @include('member-portal.partials.sidebar', ['active' => 'records', 'mode' => 'links'])
```

Keep the `sidebar-overlay` div that follows.

- [ ] **Step 3: Replace the inline bottom-nav with the partial**

Find the inline bottom-nav: `<nav class="bottom-nav" aria-label="Bottom navigation">` through its closing `</nav>` (and the preceding `{{-- ── Bottom Nav (mobile) ── --}}` comment if present). Delete the whole block and replace it with:

```blade
@include('member-portal.partials.bottom-nav', ['active' => 'records', 'mode' => 'links'])
```

- [ ] **Step 4: Verify the page renders**

Run: `php artisan view:clear`
Then: `php artisan test --filter='MemberPortalIntegrationTest::test_records_page_renders'`

Note: that test is created in Task 11. If it does not exist yet, instead run:
`php artisan tinker --execute="echo view('member-portal.isgh-records', ['profile' => null, 'email' => 'x@y.com'])->render() ? 'RENDERS' : 'EMPTY';"`
Expected: prints `RENDERS` (or page HTML) with no Blade exception.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/isgh-records.blade.php
git commit -m "feat: ISGH Records page uses shared sidebar + bottom-nav partials"
```

---

## Task 7: Newsletter page — both partials, logout CSS

**Files:**
- Modify: `resources/views/member-portal/newsletter.blade.php`

- [ ] **Step 1: Add the `.nav-logout` CSS**

In `resources/views/member-portal/newsletter.blade.php`, find the CSS rule `.nav-item.active:hover` in the `<style>` block. Immediately AFTER its closing `}`, add:

```css
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Replace the inline sidebar with the partial**

Find `<aside class="sidebar" id="sidebar">` through its matching `</aside>` (followed by the `sidebar-overlay` div). Delete the whole `<aside>…</aside>` block and replace it with:

```blade
  @include('member-portal.partials.sidebar', ['active' => 'newsletter', 'mode' => 'links'])
```

Keep the `sidebar-overlay` div that follows.

- [ ] **Step 3: Replace the inline bottom-nav with the partial**

Find `<nav class="bottom-nav" aria-label="Bottom navigation">` through its closing `</nav>` (and the preceding bottom-nav comment if present). Delete the whole block and replace it with:

```blade
@include('member-portal.partials.bottom-nav', ['active' => 'newsletter', 'mode' => 'links'])
```

- [ ] **Step 4: Verify the page renders**

Run: `php artisan view:clear`
Then: `php artisan tinker --execute="echo view('member-portal.newsletter', ['profile' => null, 'email' => 'x@y.com'])->render() ? 'RENDERS' : 'EMPTY';"`
Expected: prints page HTML with no Blade exception.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/newsletter.blade.php
git commit -m "feat: Newsletter page uses shared sidebar + bottom-nav partials"
```

---

## Task 8: Financial Report page — both partials, logout CSS

**Files:**
- Modify: `resources/views/member-portal/financial-report.blade.php`

- [ ] **Step 1: Add the `.nav-logout` CSS**

In `resources/views/member-portal/financial-report.blade.php`, find the CSS rule `.nav-item.active:hover` in the `<style>` block. Immediately AFTER its closing `}`, add:

```css
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Replace the inline sidebar with the partial**

Find `<aside class="sidebar" id="sidebar">` through its matching `</aside>` (followed by the `sidebar-overlay` div). Delete the whole `<aside>…</aside>` block and replace it with:

```blade
  @include('member-portal.partials.sidebar', ['active' => 'financial-report', 'mode' => 'links'])
```

Keep the `sidebar-overlay` div that follows.

- [ ] **Step 3: Replace the inline bottom-nav with the partial**

Find `<nav class="bottom-nav" aria-label="Bottom navigation">` through its closing `</nav>` (and the preceding bottom-nav comment if present). Delete the whole block and replace it with:

```blade
@include('member-portal.partials.bottom-nav', ['active' => 'financial-report', 'mode' => 'links'])
```

- [ ] **Step 4: Verify the page renders**

Run: `php artisan view:clear`
Then: `php artisan tinker --execute="echo view('member-portal.financial-report', ['profile' => null, 'email' => 'x@y.com'])->render() ? 'RENDERS' : 'EMPTY';"`
Expected: prints page HTML with no Blade exception.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/financial-report.blade.php
git commit -m "feat: Financial Report page uses shared sidebar + bottom-nav partials"
```

---

## Task 9: Updates page — both partials, logout CSS

**Files:**
- Modify: `resources/views/member-portal/updates.blade.php`

- [ ] **Step 1: Add the `.nav-logout` CSS**

In `resources/views/member-portal/updates.blade.php`, find the CSS rule `.nav-item.active:hover` in the `<style>` block. Immediately AFTER its closing `}`, add:

```css
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Replace the inline sidebar with the partial**

Find `<aside class="sidebar" id="sidebar">` through its matching `</aside>` (followed by the `sidebar-overlay` div). Delete the whole `<aside>…</aside>` block and replace it with:

```blade
  @include('member-portal.partials.sidebar', ['active' => 'updates', 'mode' => 'links'])
```

Keep the `sidebar-overlay` div that follows.

- [ ] **Step 3: Replace the inline bottom-nav with the partial**

Find `<nav class="bottom-nav" aria-label="Bottom navigation">` through its closing `</nav>` (and the preceding bottom-nav comment if present). Delete the whole block and replace it with:

```blade
@include('member-portal.partials.bottom-nav', ['active' => 'updates', 'mode' => 'links'])
```

- [ ] **Step 4: Verify the page renders**

Run: `php artisan view:clear`
Then: `php artisan tinker --execute="echo view('member-portal.updates', ['profile' => null, 'email' => 'x@y.com'])->render() ? 'RENDERS' : 'EMPTY';"`
Expected: prints page HTML with no Blade exception.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/updates.blade.php
git commit -m "feat: Updates page uses shared sidebar + bottom-nav partials"
```

---

## Task 10: Nominees Training page — both partials, logout CSS

**Files:**
- Modify: `resources/views/member-portal/nominees-training.blade.php`

- [ ] **Step 1: Add the `.nav-logout` CSS**

In `resources/views/member-portal/nominees-training.blade.php`, find the CSS rule `.nav-item.active:hover` in the `<style>` block. Immediately AFTER its closing `}`, add:

```css
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }
```

- [ ] **Step 2: Replace the inline sidebar with the partial**

Find `<aside class="sidebar" id="sidebar">` through its matching `</aside>` (followed by the `sidebar-overlay` div). Delete the whole `<aside>…</aside>` block and replace it with:

```blade
  @include('member-portal.partials.sidebar', ['active' => 'nominees-training', 'mode' => 'links'])
```

Keep the `sidebar-overlay` div that follows.

- [ ] **Step 3: Replace the inline bottom-nav with the partial**

Find `<nav class="bottom-nav" aria-label="Bottom navigation">` through its closing `</nav>` (and the preceding bottom-nav comment if present). Delete the whole block and replace it with:

```blade
@include('member-portal.partials.bottom-nav', ['active' => 'nominees-training', 'mode' => 'links'])
```

- [ ] **Step 4: Verify the page renders**

Run: `php artisan view:clear`
Then: `php artisan tinker --execute="echo view('member-portal.nominees-training', ['profile' => null, 'email' => 'x@y.com'])->render() ? 'RENDERS' : 'EMPTY';"`
Expected: prints page HTML with no Blade exception.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/nominees-training.blade.php
git commit -m "feat: Nominees Training page uses shared sidebar + bottom-nav partials"
```

---

## Task 11: Feature tests — route renders + logout

**Files:**
- Modify: `tests/Feature/MemberPortalIntegrationTest.php`

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Feature/MemberPortalIntegrationTest.php`, immediately before the class's final closing `}`:

```php
    /**
     * Every authenticated member-portal page renders 200 with the sidebar
     * (which now includes the logout button) present.
     */
    public function test_all_member_portal_pages_render_with_logout_button(): void
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

        foreach ([
            '/member-portal/dashboard',
            '/member-portal/profile',
            '/member-portal/payments',
            '/member-portal/records',
            '/member-portal/newsletter',
            '/member-portal/financial-report',
            '/member-portal/updates',
            '/member-portal/nominees-training',
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Logout')
                ->assertSee(route('member-portal.logout'), false);
        }
    }

    public function test_logout_clears_session_and_redirects_to_login(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->post('/member-portal/logout')
            ->assertRedirect('/member-portal/login');

        // After logout the protected area must bounce back to login.
        $this->get('/member-portal/dashboard')
            ->assertRedirect('/member-portal/login');
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter='test_all_member_portal_pages_render_with_logout_button'`
Expected: FAIL — before Tasks 1–10 the pages have no "Logout" text / logout route. If Tasks 1–10 are already done, this test may already PASS; in that case note it and continue (the test is still valuable as a regression guard).

Run: `php artisan test --filter='test_logout_clears_session_and_redirects_to_login'`
Expected: PASS — the logout route already exists, so this test passes immediately. It is included as a regression guard.

- [ ] **Step 3: Run both new tests to confirm they pass**

Run: `php artisan test --filter='MemberPortalIntegrationTest'`
Expected: PASS — all tests in the class green (the original suite plus the 2 new tests).

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/MemberPortalIntegrationTest.php
git commit -m "test: cover member-portal page routing and logout"
```

---

## Task 12: Full verification

- [ ] **Step 1: Clear caches and run the member-portal suites**

Run: `php artisan view:clear`
Then: `php artisan test tests/Unit/MemberProfileTest.php tests/Feature/MemberPortalIntegrationTest.php tests/Feature/MemberPortalServiceTest.php`
Expected: PASS — all member-portal tests green.

- [ ] **Step 2: Confirm no member-portal page has leftover dead nav links**

Run: `grep -rn 'href="#"' resources/views/member-portal/partials/sidebar.blade.php resources/views/member-portal/partials/bottom-nav.blade.php`
Expected: NO output — neither partial contains a dead `#` link. (The dashboard's `showPage`-driven items use `href="#"` with an `onclick`; that is in dashboard.blade.php via `mode='spa'`, not in the partials, so this grep stays clean.)

- [ ] **Step 3: Confirm all 6 inline sidebars were removed**

Run: `grep -rln '<aside class="sidebar"' resources/views/member-portal/`
Expected: NO output — every page now `@include`s the partial; no inline `<aside class="sidebar">` remains in any view.

- [ ] **Step 4: Final commit (only if uncommitted changes remain)**

```bash
git status
# if clean, nothing to do; otherwise:
git add -A && git commit -m "chore: finalize member-portal routing & logout"
```

---

## Self-Review Notes

- **Spec coverage:** sidebar 8 routes (Task 1) ✓; logout button as POST form (Task 1) ✓; new bottom-nav partial (Task 2) ✓; dashboard bottom-nav + quicklinks + SPA preserved via `mode='spa'` (Task 3) ✓; payments bottom-nav added where none existed (Task 4) ✓; profile both partials + quicklinks (Task 5) ✓; 5 static pages both partials (Tasks 6–10) ✓; modal-trigger quicklinks left untouched (Tasks 3, 5) ✓; tests for route renders + logout (Task 11) ✓; full verification incl. dead-link/inline-sidebar grep (Task 12) ✓.
- **Type/name consistency:** `$active` keys (`dashboard`, `profile`, `payments`, `records`, `newsletter`, `financial-report`, `updates`, `nominees-training`) and `$mode` values (`spa`, `links`) are identical across both partials and every `@include`. CSS classes `.nav-logout-form` / `.nav-item.nav-logout` are consistent across Tasks 1, 3, 4, 5, 6–10. Route names match `routes/web.php`.
- **Placeholder scan:** every code step shows complete content; no TBD/TODO; the only conditional instruction (Task 6 Step 4) gives an explicit fallback command.
- **Note for the engineer:** the dashboard's embedded `#profilePage` section and `showPage()` are intentionally NOT removed — SPA mode is preserved. Only the bottom-nav and quicklinks change in `dashboard.blade.php`.

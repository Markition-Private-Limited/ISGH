# Member Portal Payment & Invoice Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a dedicated "Payment & Invoice" page to the member portal that lists every WildApricot invoice with payment summary, next renewal, and a searchable/paginated invoice table, wired into the sidebar.

**Architecture:** New `GET /member-portal/payments` route → `MemberPortalController::payments()` → new `payments.blade.php` view. Reuses the existing `member.portal.auth` middleware, `MemberPortalService` cached bundle, and `MemberProfile` view-model — no new WildApricot API calls. The sidebar is extracted from `dashboard.blade.php` into a shared partial that renders SPA `onclick` links on the dashboard and real `href` links on the payments page.

**Tech Stack:** Laravel 11 (Blade, PHPUnit), vanilla JS, inline CSS following the dashboard's existing design tokens.

---

## File Structure

- **Create** `app/Support/MemberProfile.php` — *modify*: add `billingPeriod()` helper.
- **Create** `resources/views/member-portal/partials/sidebar.blade.php` — shared sidebar nav, parameterized by `$active` and `$mode`.
- **Create** `resources/views/member-portal/payments.blade.php` — the new page.
- **Modify** `routes/web.php` — add the `payments` route.
- **Modify** `app/Http/Controllers/MemberPortalController.php` — add `payments()` method.
- **Modify** `resources/views/member-portal/dashboard.blade.php` — replace inline sidebar with the partial; link mobile bottom-nav "Payments" item.
- **Modify** `tests/Unit/MemberProfileTest.php` — tests for `billingPeriod()`.
- **Modify** `tests/Feature/MemberPortalIntegrationTest.php` — test for the payments route.

---

## Task 1: `MemberProfile::billingPeriod()` helper

Derives the billing-period label for an invoice from the membership level name.

**Files:**
- Modify: `app/Support/MemberProfile.php`
- Test: `tests/Unit/MemberProfileTest.php`

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Unit/MemberProfileTest.php` (before the closing `}`):

```php
public function test_billing_period_annual_adds_one_year(): void
{
    $b = $this->bundle();
    $b['contact']['MembershipLevel'] = ['Name' => 'Individual Membership'];
    $p = new MemberProfile($b);

    $period = $p->billingPeriod(['date' => '2026-01-15']);
    $this->assertSame('Jan 2026 – Jan 2027', $period);
}

public function test_billing_period_checkomatic_adds_one_month(): void
{
    $b = $this->bundle();
    $b['contact']['MembershipLevel'] = ['Name' => 'Checkomatic'];
    $p = new MemberProfile($b);

    $period = $p->billingPeriod(['date' => '2026-01-15']);
    $this->assertSame('Jan 2026 – Feb 2026', $period);
}

public function test_billing_period_lifetime_is_blank(): void
{
    $b = $this->bundle();
    $b['contact']['MembershipLevel'] = ['Name' => 'Lifetime'];
    $p = new MemberProfile($b);

    $this->assertSame('', $p->billingPeriod(['date' => '2026-01-15']));
}

public function test_billing_period_blank_for_missing_date(): void
{
    $p = new MemberProfile($this->bundle());
    $this->assertSame('', $p->billingPeriod(['date' => '']));
    $this->assertSame('', $p->billingPeriod([]));
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=test_billing_period`
Expected: FAIL — `Call to undefined method App\Support\MemberProfile::billingPeriod()`

- [ ] **Step 3: Implement the helper**

In `app/Support/MemberProfile.php`, add this method after `nextPayment()` (anywhere among the public accessors is fine):

```php
/**
 * Billing-period label for an invoice row, derived from the membership level:
 *  - lifetime levels never renew → '' (blank)
 *  - checkomatic levels bill monthly → invoice month + 1 month
 *  - all other (annual) levels → invoice month + 1 year
 * Expects an invoice array as produced in the constructor (uses 'date').
 * Returns '' when the invoice date is missing or unparseable.
 */
public function billingPeriod(array $invoice): string
{
    $date = (string) ($invoice['date'] ?? '');
    if ($date === '') {
        return '';
    }

    $level = strtolower($this->level);
    if (str_contains($level, 'lifetime')) {
        return '';
    }

    try {
        $start = Carbon::parse($date);
    } catch (\Throwable) {
        return '';
    }

    $end = str_contains($level, 'checkomatic')
        ? $start->copy()->addMonth()
        : $start->copy()->addYear();

    return $start->format('M Y') . ' – ' . $end->format('M Y');
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=test_billing_period`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Support/MemberProfile.php tests/Unit/MemberProfileTest.php
git commit -m "feat: add MemberProfile::billingPeriod helper"
```

---

## Task 2: `payments` route and controller method

Adds the route and a controller method mirroring `profile()`.

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/MemberPortalController.php`
- Test: `tests/Feature/MemberPortalIntegrationTest.php`

- [ ] **Step 1: Write the failing test**

Add this method to `tests/Feature/MemberPortalIntegrationTest.php` (before the closing `}`):

```php
public function test_payments_page_renders_invoice_history(): void
{
    Http::fake([
        'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
            'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
            'Email' => 'tauqeer@example.com', 'Status' => 'Active',
            'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
        ], 200),
        'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
            ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
        ], 200),
        'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
        'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => [
            ['Id' => 1, 'DocumentNumber' => 'INV-2026-0001', 'Value' => 20.0,
             'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00', 'Url' => 'https://wa.test/inv/1'],
        ]], 200),
        'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
    ]);

    $this->withSession([
        'member_portal_authenticated' => true,
        'member_portal_contact_id'    => 999,
        'member_portal_email'         => 'tauqeer@example.com',
    ]);

    $this->get('/member-portal/payments')
        ->assertOk()
        ->assertSee('Payments and Invoice')
        ->assertSee('INV-2026-0001');
}

public function test_payments_page_requires_authentication(): void
{
    $this->get('/member-portal/payments')
        ->assertRedirect('/member-portal/login');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_payments_page`
Expected: FAIL — route `/member-portal/payments` returns 404.

- [ ] **Step 3: Add the route**

In `routes/web.php`, inside the `Route::middleware('member.portal.auth')->group(...)` block (after the `profile.update` line), add:

```php
        Route::get('/payments', [MemberPortalController::class, 'payments'])->name('payments');
```

The block becomes:

```php
    Route::middleware('member.portal.auth')->group(function () {
        Route::get('/dashboard', [MemberPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile',   [MemberPortalController::class, 'profile'])->name('profile');
        Route::post('/profile/update', [MemberPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/payments', [MemberPortalController::class, 'payments'])->name('payments');
    });
```

- [ ] **Step 4: Add the controller method**

In `app/Http/Controllers/MemberPortalController.php`, add this method after `profile()` (before `updateProfile()`):

```php
    public function payments(Request $request, MemberPortalService $portal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        $email     = $request->session()->get('member_portal_email');

        if (! $contactId) {
            return redirect()->route('member-portal.login');
        }

        if ($request->boolean('refresh')) {
            $portal->invalidate((int) $contactId);
        }

        $bundle  = $portal->getBundle((int) $contactId);
        $profile = new MemberProfile($bundle);

        return view('member-portal.payments', compact('profile', 'email'));
    }
```

- [ ] **Step 5: Run test to verify route + auth pass (view will still fail)**

Run: `php artisan test --filter=test_payments_page_requires_authentication`
Expected: PASS (the redirect works without the view).

The `test_payments_page_renders_invoice_history` test will still fail with `View [member-portal.payments] not found` — that is expected; Task 4 creates the view.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/MemberPortalController.php tests/Feature/MemberPortalIntegrationTest.php
git commit -m "feat: add member-portal payments route and controller"
```

---

## Task 3: Shared sidebar partial

Extracts the sidebar into a reusable partial. It accepts:
- `$active` — one of `'dashboard'`, `'profile'`, `'payments'` (which item is highlighted).
- `$mode` — `'spa'` (dashboard: Dashboard/Profile use `onclick` `showPage()`) or `'links'` (other pages: all items are real `href`s).

**Files:**
- Create: `resources/views/member-portal/partials/sidebar.blade.php`

- [ ] **Step 1: Create the partial**

Create `resources/views/member-portal/partials/sidebar.blade.php` with this exact content:

```blade
{{--
  Member-portal sidebar.
  @param string $active  'dashboard' | 'profile' | 'payments'
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
      <a href="{{ route('member-portal.dashboard') }}#profile" class="nav-item {{ $active === 'profile' ? 'active' : '' }}">
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

    {{-- Remaining items — not yet wired (dead links, unchanged from original) --}}
    <a href="#" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/>
      </svg>
      ISGH Records
    </a>
    <a href="#" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/>
      </svg>
      Isgh Newsletter
    </a>
    <a href="#" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 14l2 2 4-4"/>
      </svg>
      Financial Report
    </a>
    <a href="#" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      Updates
    </a>
    <a href="#" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      Nominees Training &amp; Orientation
    </a>
  </nav>
</aside>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/member-portal/partials/sidebar.blade.php
git commit -m "feat: extract member-portal sidebar into shared partial"
```

---

## Task 4: Payments page view

The new page: page-shell (sidebar + topbar), Payment Summary cards, Next Renewal card, and the searchable/paginated Invoice History table.

**Files:**
- Create: `resources/views/member-portal/payments.blade.php`

- [ ] **Step 1: Create the view**

Create `resources/views/member-portal/payments.blade.php` with this exact content:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Payments &amp; Invoice — ISGH</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:#f5f6f8; --surface:#ffffff; --border:#eef0f2;
      --text:#0f172a; --text-muted:#6b7280; --text-faint:#9ca3af;
      --green:#0d7a55; --green-dark:#064e36; --green-mid:#10b981;
      --green-soft:#d8f3e4; --teal-soft:#cdebe2; --yellow-soft:#eef6c4;
      --radius:18px; --radius-sm:12px;
      --shadow:0 4px 24px rgba(15,23,42,0.05);
    }

    html, body {
      height:100%;
      font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
      background:var(--bg); color:var(--text); font-size:14px; line-height:1.5;
      -webkit-font-smoothing:antialiased; overflow-x:hidden;
    }
    a { text-decoration:none; color:inherit; }
    button { font-family:inherit; cursor:pointer; }

    /* ── Layout ── */
    .app { display:grid; grid-template-columns:248px 1fr; min-height:100vh;
           transition:grid-template-columns .3s ease; max-width:100%; overflow-x:hidden; }
    .app.sidebar-collapsed { grid-template-columns:0 1fr; }
    .app.sidebar-collapsed .sidebar { transform:translateX(-100%); }

    /* ── Sidebar ── */
    .sidebar { background:var(--surface); border-right:1px solid var(--border);
               display:flex; flex-direction:column; transition:transform .3s ease; z-index:40; }
    .sidebar-brand { display:flex; align-items:center; justify-content:space-between;
                     padding:22px 20px; border-bottom:1px solid var(--border); }
    .brand-left { display:flex; align-items:center; gap:10px; }
    .brand-logo { width:34px; height:34px; border-radius:9px; background:var(--green-soft);
                  display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .brand-logo img { width:100%; height:100%; object-fit:contain; }
    .brand-name { font-weight:700; font-size:16px; }
    .sidebar-toggle { width:34px; height:34px; border:none; background:none; border-radius:8px;
                      display:flex; align-items:center; justify-content:center; color:var(--text-muted); }
    .sidebar-toggle:hover { background:var(--bg); }
    .sidebar-nav { padding:14px 12px; display:flex; flex-direction:column; gap:3px; }
    .nav-item { display:flex; align-items:center; gap:11px; padding:11px 14px;
                border-radius:10px; font-size:14px; font-weight:500; color:var(--text-muted);
                transition:background .15s, color .15s; }
    .nav-item svg { width:18px; height:18px; flex-shrink:0; stroke-width:1.8; }
    .nav-item:hover { background:#f8fafc; color:var(--text); }
    .nav-item.active { background:var(--green); color:#fff; }
    .nav-item.active:hover { color:#fff; }

    /* ── Main ── */
    .main { display:flex; flex-direction:column; min-width:0; }
    .topbar { display:flex; align-items:center; justify-content:space-between;
              padding:18px 28px; border-bottom:1px solid var(--border); background:var(--surface); }
    .topbar-left { display:flex; align-items:center; gap:14px; }
    .hamburger { width:38px; height:38px; border:none; background:none; border-radius:9px;
                 display:none; align-items:center; justify-content:center; color:var(--text); }
    .hamburger:hover { background:var(--bg); }
    .page-title { font-size:19px; font-weight:700; }
    .user-name { font-size:14px; font-weight:600; color:var(--text); }

    .content { padding:28px; display:flex; flex-direction:column; gap:22px; }

    /* ── Summary row ── */
    .summary-row { display:grid; grid-template-columns:1.6fr 1fr; gap:22px; }
    .summary-card { background:var(--surface); border:1px solid var(--border);
                    border-radius:var(--radius); box-shadow:var(--shadow); padding:24px; }
    .summary-card h3 { font-size:15px; font-weight:700; margin-bottom:18px; }
    .summary-pair { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .summary-tile { border-radius:var(--radius-sm); padding:20px; }
    .summary-tile.all-time { background:var(--yellow-soft); }
    .summary-tile.this-year { background:var(--teal-soft); }
    .summary-tile .label { font-size:12px; font-weight:600; color:var(--text-muted); }
    .summary-tile .value { font-size:30px; font-weight:800; margin-top:24px; }

    /* ── Renewal card ── */
    .renewal-card { background:linear-gradient(135deg,#d8f3e4 0%,#cdebe2 100%);
                    border-radius:var(--radius); padding:24px; display:flex;
                    flex-direction:column; box-shadow:var(--shadow); }
    .renewal-card .r-title { font-size:15px; font-weight:700; }
    .renewal-card .r-sub { font-size:12px; color:var(--text-muted); margin-top:4px; }
    .renewal-card .r-date { font-size:24px; font-weight:800; margin-top:14px; }
    .renewal-pill { margin-top:auto; align-self:flex-end; background:var(--surface);
                    border-radius:12px; padding:12px 16px; text-align:right; }
    .renewal-pill .amt { font-size:15px; font-weight:700; }
    .renewal-pill .days { font-size:11px; color:var(--text-muted); margin-top:2px; }

    /* ── Invoice table card ── */
    .table-card { background:var(--surface); border:1px solid var(--border);
                  border-radius:var(--radius); box-shadow:var(--shadow); padding:24px; }
    .table-head { display:flex; align-items:center; justify-content:space-between;
                  gap:16px; margin-bottom:18px; flex-wrap:wrap; }
    .table-head h3 { font-size:16px; font-weight:700; }
    .search-box { position:relative; }
    .search-box input { border:1px solid var(--border); border-radius:999px;
                        padding:9px 16px 9px 36px; font-size:13px; min-width:240px;
                        outline:none; background:var(--bg); }
    .search-box input:focus { border-color:var(--green); background:var(--surface); }
    .search-box svg { position:absolute; left:13px; top:50%; transform:translateY(-50%);
                      width:15px; height:15px; color:var(--text-faint); }

    .inv-table { width:100%; border-collapse:collapse; }
    .inv-table th { text-align:left; font-size:11px; font-weight:700; letter-spacing:.04em;
                    text-transform:uppercase; color:var(--text-faint);
                    padding:10px 12px; border-bottom:1px solid var(--border); }
    .inv-table td { font-size:13px; padding:14px 12px; border-bottom:1px solid var(--border); }
    .inv-table tr:last-child td { border-bottom:none; }
    .inv-table tbody tr:hover { background:#f8fafc; }
    .inv-num { font-weight:600; }
    .inv-amount { font-weight:700; }
    .inv-view { color:var(--green); font-weight:600; display:inline-flex; align-items:center; gap:5px; }
    .inv-view.disabled { color:var(--text-faint); pointer-events:none; }
    .inv-empty { text-align:center; color:var(--text-muted); padding:32px 12px; }

    /* ── Pager ── */
    .pager { display:flex; align-items:center; justify-content:space-between;
             margin-top:18px; flex-wrap:wrap; gap:12px; }
    .pager-info { font-size:12px; color:var(--text-muted); }
    .pager-controls { display:flex; align-items:center; gap:8px; }
    .pager-btn { border:1px solid var(--border); background:var(--surface); border-radius:8px;
                 padding:7px 14px; font-size:13px; font-weight:600; color:var(--text); }
    .pager-btn:hover:not(:disabled) { background:var(--bg); }
    .pager-btn:disabled { opacity:.45; cursor:default; }
    .pager-page { width:30px; height:30px; border-radius:8px; border:1px solid var(--border);
                  background:var(--surface); font-size:13px; font-weight:600; }
    .pager-page.active { background:var(--green); color:#fff; border-color:var(--green); }

    .sidebar-overlay { position:fixed; inset:0; background:rgba(15,23,42,.45);
                       opacity:0; visibility:hidden; transition:opacity .25s; z-index:39; }
    .sidebar-overlay.open { opacity:1; visibility:visible; }

    /* ── Responsive ── */
    @media (max-width:980px) {
      .summary-row { grid-template-columns:1fr; }
    }
    @media (max-width:768px) {
      .app { grid-template-columns:1fr; }
      .sidebar { position:fixed; top:0; left:0; bottom:0; width:248px; transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); }
      .hamburger { display:flex; }
      .content { padding:18px; }
      .summary-pair { grid-template-columns:1fr; }
      .inv-table { display:block; overflow-x:auto; white-space:nowrap; }
    }
  </style>
</head>
<body>
@php /** @var \App\Support\MemberProfile $profile */ @endphp

<div class="app">

  @include('member-portal.partials.sidebar', ['active' => 'payments', 'mode' => 'links'])

  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  <div class="main">

    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <h1 class="page-title">Payments and Invoice</h1>
      </div>
      <div class="topbar-right">
        <span class="user-name">{{ $profile->fullName ?: 'Member' }}</span>
      </div>
    </header>

    <section class="content">

      {{-- ── Summary + Renewal ── --}}
      <div class="summary-row">
        <div class="summary-card">
          <h3>Payment Summary</h3>
          <div class="summary-pair">
            <div class="summary-tile all-time">
              <div class="label">Total Paid (All Time)</div>
              <div class="value">${{ number_format($profile->paidAllTime(), 2) }}</div>
            </div>
            <div class="summary-tile this-year">
              <div class="label">Current Year</div>
              <div class="value">${{ number_format($profile->paidThisYear(), 2) }}</div>
            </div>
          </div>
        </div>

        <div class="renewal-card">
          <div class="r-title">Next Renewal</div>
          <div class="r-sub">Your {{ $profile->level ?: 'Membership' }} will renew on</div>
          <div class="r-date">{{ $profile->renewalFormatted() ?: '—' }}</div>
          <div class="renewal-pill">
            <div class="amt">Amount: {{ $profile->yearlyFee ?: '—' }}</div>
            @if($profile->daysLeft() !== null)
              <div class="days">{{ $profile->daysLeft() }} days remaining</div>
            @endif
          </div>
        </div>
      </div>

      {{-- ── Invoice History ── --}}
      <div class="table-card">
        <div class="table-head">
          <h3>Invoice History</h3>
          <div class="search-box">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="invSearch" placeholder="Search invoice number…" autocomplete="off" />
          </div>
        </div>

        <table class="inv-table">
          <thead>
            <tr>
              <th>Invoice Number</th>
              <th>Membership Type</th>
              <th>Amount</th>
              <th>Payment Date</th>
              <th>Billing Period</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="invBody">
            @forelse($profile->invoices as $inv)
              <tr class="inv-row" data-number="{{ strtolower($inv['number']) }}">
                <td class="inv-num">{{ $inv['number'] }}</td>
                <td>{{ $profile->level ?: '—' }}</td>
                <td class="inv-amount">${{ number_format($inv['amount'], 2) }}</td>
                <td>{{ $inv['dateLabel'] ?: '—' }}</td>
                <td>{{ $profile->billingPeriod($inv) ?: '—' }}</td>
                <td>
                  @if(($inv['url'] ?? '#') !== '#' && $inv['url'] !== '')
                    <a href="{{ $inv['url'] }}" target="_blank" rel="noopener" class="inv-view">
                      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
                      </svg>
                      View
                    </a>
                  @else
                    <span class="inv-view disabled">View</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td class="inv-empty" colspan="6">No invoices yet</td></tr>
            @endforelse
          </tbody>
        </table>

        <div class="pager" id="invPager" style="display:none;">
          <div class="pager-info" id="pagerInfo"></div>
          <div class="pager-controls">
            <button class="pager-btn" id="pagerPrev">Previous</button>
            <span id="pagerPages" style="display:flex; gap:6px;"></span>
            <button class="pager-btn" id="pagerNext">Next</button>
          </div>
        </div>
        <div class="inv-empty" id="invNoResults" style="display:none;">No invoices match your search.</div>
      </div>

    </section>
  </div>
</div>

<script>
  // ── Sidebar drawer ──────────────────────────────────────────────────────
  (function () {
    const app     = document.querySelector('.app');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isMobile = () => window.innerWidth <= 768;

    function openDrawer()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function closeDrawer() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }

    window.toggleSidebar = function () {
      if (isMobile()) {
        sidebar.classList.contains('open') ? closeDrawer() : openDrawer();
      } else {
        app.classList.toggle('sidebar-collapsed');
      }
    };
    overlay.addEventListener('click', closeDrawer);
    window.addEventListener('resize', () => {
      if (!isMobile() && sidebar.classList.contains('open')) closeDrawer();
    });
  })();

  // ── Invoice search + pagination ─────────────────────────────────────────
  (function () {
    const PAGE_SIZE = 10;
    const allRows   = Array.from(document.querySelectorAll('.inv-row'));
    if (allRows.length === 0) return; // empty state — nothing to paginate

    const search    = document.getElementById('invSearch');
    const pager     = document.getElementById('invPager');
    const pagerInfo = document.getElementById('pagerInfo');
    const pagerPrev = document.getElementById('pagerPrev');
    const pagerNext = document.getElementById('pagerNext');
    const pagerPages= document.getElementById('pagerPages');
    const noResults = document.getElementById('invNoResults');

    let filtered = allRows.slice();
    let page = 1;

    function render() {
      const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
      if (page > totalPages) page = totalPages;

      allRows.forEach(r => r.style.display = 'none');
      const start = (page - 1) * PAGE_SIZE;
      filtered.slice(start, start + PAGE_SIZE).forEach(r => r.style.display = '');

      noResults.style.display = filtered.length === 0 ? '' : 'none';
      pager.style.display = filtered.length > PAGE_SIZE ? '' : 'none';

      pagerInfo.textContent = 'Page ' + page + ' of ' + totalPages;
      pagerPrev.disabled = page <= 1;
      pagerNext.disabled = page >= totalPages;

      pagerPages.innerHTML = '';
      for (let i = 1; i <= totalPages; i++) {
        const b = document.createElement('button');
        b.className = 'pager-page' + (i === page ? ' active' : '');
        b.textContent = i;
        b.addEventListener('click', () => { page = i; render(); });
        pagerPages.appendChild(b);
      }
    }

    search.addEventListener('input', () => {
      const q = search.value.trim().toLowerCase();
      filtered = q === '' ? allRows.slice()
                          : allRows.filter(r => r.dataset.number.includes(q));
      page = 1;
      render();
    });
    pagerPrev.addEventListener('click', () => { if (page > 1) { page--; render(); } });
    pagerNext.addEventListener('click', () => { page++; render(); });

    render();
  })();
</script>

</body>
</html>
```

- [ ] **Step 2: Run the feature test to verify the page renders**

Run: `php artisan test --filter=test_payments_page`
Expected: PASS (2 tests — `test_payments_page_renders_invoice_history` and `test_payments_page_requires_authentication`).

- [ ] **Step 3: Commit**

```bash
git add resources/views/member-portal/payments.blade.php
git commit -m "feat: add member-portal Payment & Invoice page"
```

---

## Task 5: Wire the dashboard to the shared sidebar

Replaces the inline `<aside class="sidebar">…</aside>` in the dashboard with the partial, and links the mobile bottom-nav "Payments" item.

**Files:**
- Modify: `resources/views/member-portal/dashboard.blade.php`

- [ ] **Step 1: Replace the inline sidebar with the partial**

In `resources/views/member-portal/dashboard.blade.php`, find the sidebar block. It begins at the `{{-- ── Sidebar ── --}}` comment with `<aside class="sidebar" id="sidebar">` and ends at the matching `</aside>` (the line immediately before `<div class="sidebar-overlay" id="sidebarOverlay" …>`).

Delete the entire `<aside class="sidebar" id="sidebar"> … </aside>` block (including the `{{-- ── Sidebar ── --}}` comment line) and replace it with this single line:

```blade
  {{-- ── Sidebar ── --}}
  @include('member-portal.partials.sidebar', ['active' => 'dashboard', 'mode' => 'spa'])
```

Leave the `<div class="sidebar-overlay" …>` line and everything else untouched.

- [ ] **Step 2: Link the mobile bottom-nav "Payments" item**

In the same file, find the bottom-nav "Payments" item — an `<a href="#" class="bn-item">` whose text content is `Payments` (it contains a `<rect x="2" y="6" …>` SVG). Change its opening tag from:

```blade
  <a href="#" class="bn-item">
```

to:

```blade
  <a href="{{ route('member-portal.payments') }}" class="bn-item">
```

(Only that one `<a>` — the one whose visible label is `Payments`. Do not touch the Dashboard, Profile, Records, or Reports items.)

- [ ] **Step 3: Run the full member-portal feature suite**

Run: `php artisan test --filter=MemberPortalIntegrationTest`
Expected: PASS (all tests — dashboard, profile, payments, update).

This confirms the dashboard still renders after the sidebar swap.

- [ ] **Step 4: Manually verify the dashboard sidebar still switches pages**

Run: `php artisan serve` (if not already running), then in a browser sign into the member portal and confirm:
- The sidebar "Profile" item still toggles the profile section (SPA, no reload).
- The sidebar "Payment & Invoice" item navigates to `/member-portal/payments`.
- On the payments page, the sidebar "Dashboard" item navigates back.

- [ ] **Step 5: Commit**

```bash
git add resources/views/member-portal/dashboard.blade.php
git commit -m "feat: use shared sidebar partial in dashboard, link payments nav"
```

---

## Task 6: Full verification

- [ ] **Step 1: Run the complete test suite**

Run: `php artisan test`
Expected: PASS — all tests green, including the new `billingPeriod` and `payments_page` tests.

- [ ] **Step 2: Verify no leftover dead "Payment & Invoice" link**

Run: `php artisan test --filter=MemberPortalIntegrationTest::test_dashboard_renders_member_data`
Expected: PASS — confirms the dashboard renders with the partial.

- [ ] **Step 3: Final commit (if any uncommitted changes remain)**

```bash
git status
# if clean, nothing to do; otherwise:
git add -A && git commit -m "chore: finalize member-portal payment & invoice page"
```

---

## Self-Review Notes

- **Spec coverage:** separate route (Task 2) ✓; client-side search + pagination (Task 4 JS) ✓; WildApricot data as-is — `number`/`url`/`dateLabel` from `MemberProfile::$invoices` ✓; Payment Summary + Next Renewal + Invoice History sections (Task 4) ✓; billing-period by membership type (Task 1) ✓; shared sidebar partial + bottom-nav link (Tasks 3, 5) ✓; empty/auth handling (Tasks 2, 4) ✓.
- **Type consistency:** `billingPeriod(array $invoice)` consumes the `'date'` key produced in the `MemberProfile` constructor — consistent across Task 1 and Task 4. `$mode` / `$active` partial params consistent across Tasks 3, 4, 5.
- **Placeholder scan:** no TBD/TODO; every code step shows complete content.

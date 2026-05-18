# Invoice Detail Modal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the green "View" button in the member portal's Invoice History table open an in-app modal showing live invoice details fetched from WildApricot, styled to match the approved mockup.

**Architecture:** A new `WildApricotService::getInvoiceById()` fetches one invoice from `GET /accounts/{accountId}/invoices/{invoiceId}`. A new authenticated controller endpoint `invoiceDetail()` guards ownership against the member's cached bundle, cross-references local `Renewal`/`LevelChange` tables for Stripe card details, and returns normalized JSON. The payments Blade view gets a modal whose "View" buttons `fetch()` that endpoint and populate it.

**Tech Stack:** Laravel 11, PHP 8.2, Blade, vanilla JS `fetch`, PHPUnit feature tests with `Http::fake()`.

**Reference spec:** `docs/superpowers/specs/2026-05-19-invoice-detail-modal-design.md`

---

## File Structure

- `app/Services/WildApricotService.php` — add `getInvoiceById()` near `getInvoicesForContact()` (~line 1667).
- `app/Http/Controllers/MemberPortalController.php` — add `invoiceDetail()` action.
- `routes/web.php` — add one route inside the `member.portal.auth` group.
- `resources/views/member-portal/payments.blade.php` — modal markup, CSS, JS; change the View cell.
- `tests/Feature/InvoiceDetailTest.php` — new feature test file.

---

## Task 1: `WildApricotService::getInvoiceById()`

**Files:**
- Modify: `app/Services/WildApricotService.php` (insert after `getInvoicesForContact()`, which ends at line 1667)
- Test: `tests/Feature/InvoiceDetailTest.php` (create)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/InvoiceDetailTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\WildApricotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoiceDetailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
    }

    public function test_get_invoice_by_id_returns_invoice_array(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/156' => Http::response([
                'Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
                'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00',
            ], 200),
        ]);

        $invoice = app(WildApricotService::class)->getInvoiceById(156);

        $this->assertSame('INV-2025-0156', $invoice['DocumentNumber']);
        $this->assertSame(156, $invoice['Id']);
    }

    public function test_get_invoice_by_id_returns_null_on_api_error(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/999' => Http::response([], 404),
        ]);

        $this->assertNull(app(WildApricotService::class)->getInvoiceById(999));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InvoiceDetailTest`
Expected: FAIL — `Call to undefined method App\Services\WildApricotService::getInvoiceById()`

- [ ] **Step 3: Implement `getInvoiceById()`**

In `app/Services/WildApricotService.php`, immediately after the closing `}` of `getInvoicesForContact()` (line 1667), insert:

```php

    // ─── MEMBER PORTAL — SINGLE INVOICE ─────────────────────────────────────
    // GET /accounts/{accountId}/invoices/{invoiceId}
    // Returns the full invoice document, or null on failure.

    public function getInvoiceById(int $invoiceId): ?array
    {
        try {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/invoices/{$invoiceId}");
            if (! $r->successful()) {
                Log::warning('WA getInvoiceById: API error', ['status' => $r->status(), 'invoice_id' => $invoiceId]);
                return null;
            }
            $body = $r->json();
            return is_array($body) ? $body : null;
        } catch (\Throwable $e) {
            Log::error('WA getInvoiceById exception', ['invoice_id' => $invoiceId, 'error' => $e->getMessage()]);
            return null;
        }
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InvoiceDetailTest`
Expected: PASS — 2 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/WildApricotService.php tests/Feature/InvoiceDetailTest.php
git commit -m "feat: add WildApricotService::getInvoiceById"
```

---

## Task 2: `invoiceDetail` route + controller endpoint

**Files:**
- Modify: `routes/web.php` (inside `member.portal.auth` group, after the `/payments` route at line 72)
- Modify: `app/Http/Controllers/MemberPortalController.php` (add `invoiceDetail()` after `payments()`, which ends at line 234)
- Test: `tests/Feature/InvoiceDetailTest.php` (append tests)

- [ ] **Step 1: Add the failing tests**

Append these methods to `tests/Feature/InvoiceDetailTest.php` (inside the class, before the closing `}`):

```php
    /** Seed a member bundle in cache and authenticate the session for contact 999. */
    private function seedMember(array $invoices = []): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => $invoices, 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);
    }

    public function test_invoice_detail_requires_authentication(): void
    {
        $this->getJson('/member-portal/invoice/156')
            ->assertStatus(401);
    }

    public function test_invoice_detail_rejects_invoice_not_owned_by_member(): void
    {
        // Member owns invoice 156 only; requesting 777 must 404 without ever
        // calling WildApricot.
        $this->seedMember([
            ['Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
             'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
        ]);

        $this->getJson('/member-portal/invoice/777')
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_invoice_detail_returns_paid_invoice_with_payment(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/156' => Http::response([
                'Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
                'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00',
            ], 200),
        ]);

        \App\Models\Renewal::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'membership_type' => 'individual', 'amount_cents' => 2000, 'currency' => 'usd',
            'status' => 'succeeded', 'wa_invoice_id' => 156, 'processed' => true,
            'payment_method' => 'card', 'card_brand' => 'visa', 'card_last4' => '4242',
            'paid_at' => '2026-01-15 10:00:00',
        ]);

        $this->seedMember([
            ['Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
             'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
        ]);

        $res = $this->getJson('/member-portal/invoice/156')->assertOk();

        $res->assertJson([
            'success' => true,
            'invoice' => [
                'number'         => 'INV-2025-0156',
                'isPaid'         => true,
                'memberName'     => 'Tauqeer Alam',
                'membershipType' => 'Individual Membership',
            ],
        ]);
        $this->assertStringContainsString('4242', $res->json('invoice.payment.method'));
    }

    public function test_invoice_detail_omits_payment_for_unpaid_invoice(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/200' => Http::response([
                'Id' => 200, 'DocumentNumber' => 'INV-2025-0200', 'Value' => 30.0,
                'IsPaid' => false, 'CreatedDate' => '2026-02-01T00:00:00',
            ], 200),
        ]);

        $this->seedMember([
            ['Id' => 200, 'DocumentNumber' => 'INV-2025-0200', 'Value' => 30.0,
             'IsPaid' => false, 'CreatedDate' => '2026-02-01T00:00:00'],
        ]);

        $this->getJson('/member-portal/invoice/200')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'invoice' => ['isPaid' => false, 'status' => 'Unpaid', 'payment' => null],
            ]);
    }
```

Add `use Illuminate\Foundation\Testing\RefreshDatabase;` to the imports and `use RefreshDatabase;` as the first line inside the class body (the Renewal `create()` needs a migrated database).

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=InvoiceDetailTest`
Expected: FAIL — the 4 new tests return 404 (route not registered).

- [ ] **Step 3: Register the route**

In `routes/web.php`, inside the `member.portal.auth` group, immediately after the `/payments` line (line 72), add:

```php
        Route::get('/invoice/{invoiceId}', [MemberPortalController::class, 'invoiceDetail'])
            ->whereNumber('invoiceId')->name('invoice-detail');
```

- [ ] **Step 4: Implement the `invoiceDetail()` action**

In `app/Http/Controllers/MemberPortalController.php`, after the `payments()` method (ends line 234), add:

```php

    // ── Invoice detail (modal) ────────────────────────────────────────────

    /**
     * JSON detail for a single invoice, shown in the payments-page modal.
     * Guards that the invoice belongs to the logged-in member, fetches it
     * from WildApricot, and enriches paid invoices with card details from
     * the local Renewal / LevelChange Stripe records.
     */
    public function invoiceDetail(Request $request, MemberPortalService $portal, WildApricotService $wa, int $invoiceId)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Please sign in again.'], 401);
        }

        $profile = new MemberProfile($portal->getBundle((int) $contactId));

        // Ownership guard — the invoice ID must be one of the member's own.
        $owned = collect($profile->invoices)->firstWhere('id', $invoiceId);
        if (! $owned) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $invoice = $wa->getInvoiceById($invoiceId);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Could not load invoice details.'], 502);
        }

        $isPaid = (bool) ($invoice['IsPaid'] ?? $owned['isPaid'] ?? false);
        $issueDate = $owned['date'] ?: '';

        $payment = null;
        if ($isPaid) {
            $stripe = LevelChange::where('wa_invoice_id', $invoiceId)->first()
                ?? Renewal::where('wa_invoice_id', $invoiceId)->first();

            $paymentDate = $stripe?->paid_at?->format('Y-m-d') ?: $issueDate;
            $method = 'Online Payment';
            if ($stripe && $stripe->card_last4) {
                $brand  = ucwords((string) ($stripe->card_brand ?: 'Card'));
                $method = "{$brand} (**** {$stripe->card_last4})";
            }

            $payment = [
                'invoiceDate' => $issueDate,
                'method'      => $method,
                'paymentDate' => $paymentDate,
            ];
        }

        return response()->json([
            'success' => true,
            'invoice' => [
                'number'         => (string) ($invoice['DocumentNumber'] ?? $owned['number']),
                'issueDate'      => $issueDate,
                'billingPeriod'  => $profile->billingPeriod($owned),
                'isPaid'         => $isPaid,
                'status'         => $isPaid ? 'Paid' : 'Unpaid',
                'amount'         => (float) ($invoice['Value'] ?? $owned['amount']),
                'currency'       => 'USD',
                'memberName'     => $profile->fullName ?: 'Member',
                'membershipType' => $profile->level ?: '—',
                'payment'        => $payment,
            ],
        ]);
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=InvoiceDetailTest`
Expected: PASS — 6 tests.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/MemberPortalController.php tests/Feature/InvoiceDetailTest.php
git commit -m "feat: add member-portal invoice detail endpoint"
```

---

## Task 3: Modal markup, CSS, and View-button wiring in the payments view

**Files:**
- Modify: `resources/views/member-portal/payments.blade.php`
- Test: `tests/Feature/InvoiceDetailTest.php` (append one test)

- [ ] **Step 1: Add the failing test**

Append to `tests/Feature/InvoiceDetailTest.php` (inside the class):

```php
    public function test_payments_page_renders_invoice_modal_and_view_buttons(): void
    {
        $this->seedMember([
            ['Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
             'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
        ]);

        $this->get('/member-portal/payments')
            ->assertOk()
            ->assertSee('id="invoiceModal"', false)
            ->assertSee('data-invoice-id="156"', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan view:clear && php artisan test --filter=test_payments_page_renders_invoice_modal_and_view_buttons`
Expected: FAIL — `id="invoiceModal"` not found.

- [ ] **Step 3: Add the modal CSS**

In `resources/views/member-portal/payments.blade.php`, inside the `<style>` block, immediately before the closing `/* ── Responsive ── */` comment (line 162), add:

```css
    /* ── Invoice detail modal ── */
    .inv-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55);
                         display:none; align-items:flex-start; justify-content:center;
                         padding:40px 16px; z-index:60; overflow-y:auto; }
    .inv-modal-overlay.open { display:flex; }
    .inv-modal { background:var(--surface); border-radius:20px; width:100%;
                 max-width:420px; box-shadow:0 24px 60px rgba(15,23,42,.25);
                 overflow:hidden; }
    .inv-modal-head { display:flex; align-items:flex-start; justify-content:space-between;
                      padding:22px 24px 8px; }
    .inv-modal-head h2 { font-size:18px; font-weight:700; }
    .inv-modal-head p { font-size:12px; color:var(--text-muted); margin-top:3px; }
    .inv-modal-close { width:30px; height:30px; border:none; background:none;
                       border-radius:8px; color:var(--text-muted); font-size:20px;
                       line-height:1; flex-shrink:0; }
    .inv-modal-close:hover { background:var(--bg); }
    .inv-modal-body { padding:8px 24px 24px; }

    .inv-banner { background:linear-gradient(135deg,#0d7a55 0%,#064e36 100%);
                  border-radius:14px; padding:18px 20px; color:#fff; position:relative; }
    .inv-banner .b-label { font-size:11px; opacity:.8; }
    .inv-banner .b-number { font-size:22px; font-weight:800; margin-top:2px; }
    .inv-banner .b-pill { position:absolute; top:16px; right:18px; background:#fff;
                          color:var(--green-dark); font-size:11px; font-weight:700;
                          padding:4px 12px; border-radius:999px; }
    .inv-banner .b-pill.unpaid { background:#fee2e2; color:#b91c1c; }
    .inv-banner .b-row { display:flex; justify-content:space-between; margin-top:18px; }
    .inv-banner .b-row .b-cell .b-label { display:block; }
    .inv-banner .b-row .b-cell .b-val { font-size:13px; font-weight:700; margin-top:2px; }
    .inv-banner .b-row .b-cell.right { text-align:right; }

    .inv-section-title { display:flex; align-items:center; gap:8px; font-size:14px;
                         font-weight:700; margin:22px 0 12px; }
    .inv-section-title svg { width:16px; height:16px; color:var(--green); }
    .inv-data { background:var(--bg); border-radius:12px; padding:14px 16px; }
    .inv-data .d-row { display:flex; justify-content:space-between; padding:6px 0;
                       font-size:13px; }
    .inv-data .d-row .d-key { color:var(--text-muted); }
    .inv-data .d-row .d-val { font-weight:700; }
    .inv-data .d-divider { border-top:1px solid var(--border); margin:8px 0; }
    .inv-data .d-row.total .d-key,
    .inv-data .d-row.total .d-val { font-size:15px; }

    .inv-note { background:#fef9ec; border-radius:10px; padding:12px 14px;
                margin-top:18px; font-size:12px; color:#92702a; }
    .inv-note b { color:#7c5a14; }
    .inv-modal-loading, .inv-modal-error { padding:48px 0; text-align:center;
                                           color:var(--text-muted); font-size:13px; }
    .inv-modal-error { color:#b91c1c; }
```

- [ ] **Step 4: Change the View cell to a button**

In `resources/views/member-portal/payments.blade.php`, replace the entire `<td>` action cell (lines 265-276) with:

```blade
                <td>
                  @if(($inv['id'] ?? null) !== null)
                    <button type="button" class="inv-view" data-invoice-id="{{ $inv['id'] }}">
                      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
                      </svg>
                      View
                    </button>
                  @else
                    <span class="inv-view disabled">View</span>
                  @endif
                </td>
```

Also update the `.inv-view` CSS rule at line 122 so the button looks like the old link — change it to:

```css
    .inv-view { color:var(--green); font-weight:600; display:inline-flex; align-items:center;
                gap:5px; border:none; background:none; font-family:inherit; font-size:13px;
                padding:0; cursor:pointer; }
```

- [ ] **Step 5: Add the modal markup**

In `resources/views/member-portal/payments.blade.php`, immediately before the `@include('member-portal.partials.bottom-nav', ...)` line (line 299), add:

```blade
{{-- ── Invoice Detail Modal ── --}}
<div class="inv-modal-overlay" id="invoiceModal">
  <div class="inv-modal" role="dialog" aria-modal="true" aria-labelledby="invModalTitle">
    <div class="inv-modal-head">
      <div>
        <h2 id="invModalTitle">Invoice Details</h2>
        <p>View complete details of your invoice and payment information</p>
      </div>
      <button type="button" class="inv-modal-close" id="invModalClose" aria-label="Close">&times;</button>
    </div>
    <div class="inv-modal-body">
      <div class="inv-modal-loading" id="invModalLoading">Loading invoice…</div>
      <div class="inv-modal-error" id="invModalError" style="display:none;"></div>
      <div id="invModalContent" style="display:none;">
        <div class="inv-banner">
          <span class="b-pill" id="invPill">Paid</span>
          <div class="b-label">Invoice Number</div>
          <div class="b-number" id="invNumber"></div>
          <div class="b-row">
            <div class="b-cell">
              <span class="b-label">Issue Date</span>
              <span class="b-val" id="invIssueDate"></span>
            </div>
            <div class="b-cell right">
              <span class="b-label">Billing Period</span>
              <span class="b-val" id="invBillingPeriod"></span>
            </div>
          </div>
        </div>

        <div class="inv-section-title">
          <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2"/>
          </svg>
          Member Information
        </div>
        <div class="inv-data">
          <div class="d-row"><span class="d-key">Member Name:</span><span class="d-val" id="invMemberName"></span></div>
          <div class="d-row"><span class="d-key">Membership Type:</span><span class="d-val" id="invMembershipType"></span></div>
        </div>

        <div id="invPaymentBlock">
          <div class="inv-section-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
            Payment Details
          </div>
          <div class="inv-data">
            <div class="d-row"><span class="d-key">Date:</span><span class="d-val" id="invPayDate"></span></div>
            <div class="d-row"><span class="d-key">Payment Method:</span><span class="d-val" id="invPayMethod"></span></div>
            <div class="d-row"><span class="d-key">Payment Date:</span><span class="d-val" id="invPaymentDate"></span></div>
            <div class="d-divider"></div>
            <div class="d-row total"><span class="d-key">Total Amount Paid:</span><span class="d-val" id="invTotal"></span></div>
          </div>
        </div>

        <div class="inv-note">
          <b>Note:</b> <span id="invNoteText">This invoice is a record of payment received. For any questions or concerns regarding this invoice, please contact our support team.</span>
        </div>
      </div>
    </div>
  </div>
</div>
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan view:clear && php artisan test --filter=test_payments_page_renders_invoice_modal_and_view_buttons`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add resources/views/member-portal/payments.blade.php tests/Feature/InvoiceDetailTest.php
git commit -m "feat: add invoice detail modal markup to payments page"
```

---

## Task 4: Modal JS — fetch and populate

**Files:**
- Modify: `resources/views/member-portal/payments.blade.php` (the `<script>` block)

- [ ] **Step 1: Add the modal JS**

In `resources/views/member-portal/payments.blade.php`, inside the `<script>` block, immediately before its closing `</script>` (line 379), add a new IIFE:

```js
  // ── Invoice detail modal ────────────────────────────────────────────────
  (function () {
    const overlay  = document.getElementById('invoiceModal');
    if (!overlay) return;

    const loadingEl = document.getElementById('invModalLoading');
    const errorEl   = document.getElementById('invModalError');
    const contentEl = document.getElementById('invModalContent');
    const closeBtn  = document.getElementById('invModalClose');

    function open()  { overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function close() { overlay.classList.remove('open'); document.body.style.overflow = ''; }

    function showState(state) {
      loadingEl.style.display = state === 'loading' ? '' : 'none';
      errorEl.style.display   = state === 'error'   ? '' : 'none';
      contentEl.style.display = state === 'content' ? '' : 'none';
    }

    function fmtMoney(amount, currency) {
      return '$' + Number(amount).toFixed(2) + ' ' + (currency || 'USD');
    }

    function populate(inv) {
      document.getElementById('invNumber').textContent        = inv.number;
      document.getElementById('invIssueDate').textContent     = inv.issueDate || '—';
      document.getElementById('invBillingPeriod').textContent = inv.billingPeriod || '—';
      document.getElementById('invMemberName').textContent    = inv.memberName;
      document.getElementById('invMembershipType').textContent= inv.membershipType;

      const pill = document.getElementById('invPill');
      pill.textContent = inv.status;
      pill.classList.toggle('unpaid', !inv.isPaid);

      const payBlock = document.getElementById('invPaymentBlock');
      const note     = document.getElementById('invNoteText');
      if (inv.isPaid && inv.payment) {
        payBlock.style.display = '';
        document.getElementById('invPayDate').textContent     = inv.payment.invoiceDate || '—';
        document.getElementById('invPayMethod').textContent   = inv.payment.method || '—';
        document.getElementById('invPaymentDate').textContent = inv.payment.paymentDate || '—';
        document.getElementById('invTotal').textContent       = fmtMoney(inv.amount, inv.currency);
        note.textContent = 'This invoice is a record of payment received. For any questions or concerns regarding this invoice, please contact our support team.';
      } else {
        payBlock.style.display = 'none';
        note.textContent = 'This invoice has not been paid. For any questions regarding this invoice, please contact our support team.';
      }
      showState('content');
    }

    async function loadInvoice(id) {
      showState('loading');
      open();
      try {
        const res  = await fetch('/member-portal/invoice/' + encodeURIComponent(id), {
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
          errorEl.textContent = data.message || 'Could not load invoice details.';
          showState('error');
          return;
        }
        populate(data.invoice);
      } catch (e) {
        errorEl.textContent = 'Could not load invoice details. Please try again.';
        showState('error');
      }
    }

    document.querySelectorAll('.inv-view[data-invoice-id]').forEach(btn => {
      btn.addEventListener('click', () => loadInvoice(btn.dataset.invoiceId));
    });

    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && overlay.classList.contains('open')) close();
    });
  })();
```

- [ ] **Step 2: Run the full feature test file to confirm nothing regressed**

Run: `php artisan view:clear && php artisan test --filter=InvoiceDetailTest`
Expected: PASS — 7 tests.

- [ ] **Step 3: Manual smoke check**

Note: JS behaviour is not covered by PHPUnit. Verify manually if a browser is available — log into the member portal, open Payments, click "View" on an invoice, confirm the modal opens, loads, and matches the mockup; close via ×, overlay click, and Escape. If no browser is available, note this step as deferred to manual QA.

- [ ] **Step 4: Commit**

```bash
git add resources/views/member-portal/payments.blade.php
git commit -m "feat: wire invoice detail modal fetch + populate JS"
```

---

## Task 5: Full regression check

**Files:** none (verification only)

- [ ] **Step 1: Run the member-portal feature suites**

Run: `php artisan view:clear && php artisan test --filter="InvoiceDetailTest|MemberPortalIntegrationTest"`
Expected: PASS — all tests green. If `MemberPortalIntegrationTest` fails, the View-cell change in Task 3 may have affected `test_payments_page_renders_disabled_view_link_when_invoice_url_missing` — that test seeds an invoice with `Id => 7`, so it now renders a `data-invoice-id="7"` button, not a disabled span. Update that test's assertion from `->assertSee('inv-view disabled', false)` to `->assertSee('data-invoice-id="7"', false)`, since an invoice WITH an ID is now always viewable regardless of URL. Re-run.

- [ ] **Step 2: Run the complete test suite**

Run: `php artisan test`
Expected: PASS — no regressions outside the member portal.

- [ ] **Step 3: Commit any test fix from Step 1**

```bash
git add tests/Feature/MemberPortalIntegrationTest.php
git commit -m "test: update disabled-view-link test for invoice modal buttons"
```

(Skip this commit if Step 1 required no change.)

---

## Self-Review Notes

- **Spec coverage:** `getInvoiceById` (Task 1) ✓; `invoiceDetail` endpoint + ownership guard + Stripe cross-reference (Task 2) ✓; route (Task 2) ✓; modal markup/CSS/View-button (Task 3) ✓; unpaid handling — payment block hidden, status block via pill + note (Tasks 2-4) ✓; modal JS (Task 4) ✓; feature tests for ownership/paid/unpaid/auth (Task 2) ✓.
- **Type consistency:** `invoiceId` is `int` everywhere (route `whereNumber`, `getInvoiceById(int)`, `invoiceDetail(... int $invoiceId)`). The JSON `invoice` object keys (`number`, `issueDate`, `billingPeriod`, `isPaid`, `status`, `amount`, `currency`, `memberName`, `membershipType`, `payment`) match exactly between the controller response (Task 2) and the JS `populate()` (Task 4). `payment` sub-keys `invoiceDate`/`method`/`paymentDate` match.
- **Known caveat:** `MemberProfile::billingPeriod()` expects an invoice array with a `date` key (Y-m-d string); `$owned` from `$profile->invoices` has exactly that shape, so it is passed directly.

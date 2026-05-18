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
    /* The grid track is 0-wide when collapsed; give the sidebar its own width
       so translateX(-100%) fully clears it, and clip overflow so its contents
       don't spill over the page content. */
    .app.sidebar-collapsed .sidebar { width:248px; overflow:hidden; transform:translateX(-100%); transition:transform .3s ease; }

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
    /* Desktop: when the sidebar is collapsed off-screen, the topbar hamburger
       becomes the only control that can reopen it. */
    .app.sidebar-collapsed .hamburger { display:inline-flex; }
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
    .inv-view { color:var(--green); font-weight:600; display:inline-flex; align-items:center;
                gap:5px; border:none; background:none; font-family:inherit; font-size:13px;
                padding:0; cursor:pointer; }
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
      .bottom-nav { display: flex; }
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

{{-- ── Invoice Detail Modal ── --}}
<div class="inv-modal-overlay" id="invoiceModal" aria-hidden="true">
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

        <div id="invStatusBlock" style="display:none;">
          <div class="inv-section-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Payment Status
          </div>
          <div class="inv-data">
            <div class="d-row"><span class="d-key">Status:</span><span class="d-val" id="invStatusValue"></span></div>
            <div class="d-divider"></div>
            <div class="d-row total"><span class="d-key">Amount Due:</span><span class="d-val" id="invAmountDue"></span></div>
          </div>
        </div>

        <div class="inv-note">
          <b>Note:</b> <span id="invNoteText">This invoice is a record of payment received. For any questions or concerns regarding this invoice, please contact our support team.</span>
        </div>
      </div>
    </div>
  </div>
</div>

@include('member-portal.partials.bottom-nav', ['active' => 'payments', 'mode' => 'links'])

<script>
  // ── Sidebar drawer ──────────────────────────────────────────────────────
  (function () {
    const app     = document.querySelector('.app');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isMobile = () => window.innerWidth <= 768;

    function openDrawer()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function closeDrawer() {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
      // Only release the body-scroll lock if the invoice modal isn't holding it.
      const invModal = document.getElementById('invoiceModal');
      if (!invModal || !invModal.classList.contains('open')) {
        document.body.style.overflow = '';
      }
    }

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

  // ── Invoice detail modal ────────────────────────────────────────────────
  (function () {
    const overlay  = document.getElementById('invoiceModal');
    if (!overlay) return;

    const loadingEl = document.getElementById('invModalLoading');
    const errorEl   = document.getElementById('invModalError');
    const contentEl = document.getElementById('invModalContent');
    const closeBtn  = document.getElementById('invModalClose');

    function open() {
      overlay.classList.add('open');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

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

      const payBlock    = document.getElementById('invPaymentBlock');
      const statusBlock = document.getElementById('invStatusBlock');
      const note        = document.getElementById('invNoteText');
      if (inv.isPaid && inv.payment) {
        payBlock.style.display    = '';
        statusBlock.style.display = 'none';
        document.getElementById('invPayDate').textContent     = inv.payment.invoiceDate || '—';
        document.getElementById('invPayMethod').textContent   = inv.payment.method || '—';
        document.getElementById('invPaymentDate').textContent = inv.payment.paymentDate || '—';
        document.getElementById('invTotal').textContent       = fmtMoney(inv.amount, inv.currency);
        note.textContent = 'This invoice is a record of payment received. For any questions or concerns regarding this invoice, please contact our support team.';
      } else {
        payBlock.style.display    = 'none';
        statusBlock.style.display = '';
        document.getElementById('invStatusValue').textContent = inv.status;
        document.getElementById('invAmountDue').textContent   = fmtMoney(inv.amount, inv.currency);
        note.textContent = 'This invoice has not been paid. For any questions regarding this invoice, please contact our support team.';
      }
      showState('content');
    }

    async function loadInvoice(id) {
      showState('loading');
      open();
      try {
        const res = await fetch('/member-portal/invoice/' + encodeURIComponent(id), {
          headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) {
          let msg = 'Could not load invoice details.';
          try { const d = await res.json(); msg = d.message || msg; } catch (_) { /* non-JSON body */ }
          errorEl.textContent = msg;
          showState('error');
          return;
        }
        const data = await res.json();
        if (!data.success) {
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
</script>

</body>
</html>

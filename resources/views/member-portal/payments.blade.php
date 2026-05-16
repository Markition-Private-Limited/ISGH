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

@include('member-portal.partials.bottom-nav', ['active' => 'payments', 'mode' => 'links'])

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

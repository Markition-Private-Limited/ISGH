<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>ISGH Records — ISGH</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:           #f5f6f8;
      --surface:      #ffffff;
      --border:       #eef0f2;
      --text:         #0f172a;
      --text-muted:   #6b7280;
      --text-faint:   #9ca3af;
      --green:        #0d7a55;
      --green-dark:   #064e36;
      --green-mid:    #10b981;
      --radius:       18px;
      --radius-sm:    12px;
      --shadow:       0 4px 24px rgba(15, 23, 42, 0.05);
      --shadow-lg:    0 10px 40px rgba(15, 23, 42, 0.08);
    }

    html, body {
      height: 100%;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--bg);
      color: var(--text);
      font-size: 14px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }
    a { text-decoration: none; color: inherit; }
    button { font-family: inherit; cursor: pointer; }

    /* ── Layout ── */
    .app {
      display: grid;
      grid-template-columns: 248px 1fr;
      min-height: 100vh;
      transition: grid-template-columns .3s ease;
      max-width: 100%;
      overflow-x: hidden;
    }
    .app.sidebar-collapsed { grid-template-columns: 0 1fr; }
    .app.sidebar-collapsed .sidebar {
      /* The grid track is 0-wide when collapsed; give the sidebar its own
         width so translateX(-100%) fully clears it, and clip any overflow
         so its contents don't spill over the page content. */
      width: 248px;
      overflow: hidden;
      transform: translateX(-100%);
      transition: transform .3s ease;
    }

    /* ── Sidebar ── */
    .sidebar {
      background: #fff;
      border-right: 1px solid var(--border);
      padding: 22px 16px;
      position: sticky;
      top: 0;
      height: 100vh;
      display: flex;
      flex-direction: column;
      gap: 18px;
      box-shadow: 1px 0 0 rgba(15,23,42,0.02);
    }
    .sidebar-brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 4px 8px 18px;
      border-bottom: 1px solid var(--border);
    }
    .brand-left { display: flex; align-items: center; gap: 10px; }
    .brand-logo {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: #1a4a2e;
      border: 1.5px solid #c8a84b;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .brand-logo img { width: 26px; height: 26px; object-fit: contain; }
    .brand-name { font-size: 16px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }
    .sidebar-toggle {
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 6px;
      border-radius: 8px;
      display: inline-flex;
    }
    .sidebar-toggle:hover { background: var(--bg); }
    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 4px;
      flex: 1;
      overflow-y: auto;
    }
    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 14px;
      border-radius: 12px;
      color: #475569;
      font-size: 14px;
      font-weight: 500;
      transition: background .15s, color .15s;
    }
    .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; stroke-width: 1.8; }
    .nav-item:hover { background: #f8fafc; color: var(--text); }
    .nav-item.active {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
      box-shadow: 0 6px 16px rgba(13, 122, 82, 0.25);
    }
    .nav-item.active:hover { color: #fff; }
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }

    /* ── Main ── */
    .main { display: flex; flex-direction: column; min-width: 0; }
    .topbar {
      background: #fff;
      border-bottom: 1px solid var(--border);
      padding: 18px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky; top: 0; z-index: 30;
    }
    .topbar-left { display: flex; align-items: center; gap: 16px; }
    .hamburger {
      background: transparent; border: none; padding: 6px;
      border-radius: 8px; color: var(--text); display: none;
    }
    .hamburger:hover { background: var(--bg); }
    /* Desktop: when the sidebar is collapsed off-screen, the topbar hamburger
       becomes the only control that can reopen it. */
    .app.sidebar-collapsed .hamburger { display: inline-flex; }
    .page-title { font-size: 20px; font-weight: 700; color: var(--text); letter-spacing: -0.3px; }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .user-name { font-size: 14px; font-weight: 600; color: var(--text); }

    /* ── Content ── */
    .content { padding: 26px 32px 40px; }
    .section-heading {
      font-size: 22px;
      font-weight: 700;
      letter-spacing: 3px;
      color: #475569;
      margin-bottom: 22px;
    }

    /* ── Records grid ── */
    .records-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 22px;
    }
    .record-card {
      background: var(--surface);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      transition: box-shadow .18s, transform .18s;
    }
    .record-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
    .record-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
    }
    .record-icon svg { width: 24px; height: 24px; stroke-width: 1.8; }
    .ic-peach  { background: #fdebd6; color: #c2772a; }
    .ic-blue   { background: #dbeafe; color: #2563aa; }
    .ic-green  { background: #d8f3e4; color: #0d7a55; }
    .record-title { font-size: 17px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }
    .record-sub { font-size: 13px; color: var(--text-muted); margin-top: -6px; }
    .record-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: auto;
      padding-top: 8px;
    }
    .pdf-badge {
      background: #fde2e2;
      color: #d64545;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.5px;
      padding: 4px 9px;
      border-radius: 20px;
    }
    .open-link {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 13px;
      font-weight: 600;
      color: var(--green);
      transition: color .15s;
    }
    .open-link:hover { color: var(--green-dark); }
    .open-link svg { width: 14px; height: 14px; stroke-width: 2; }

    /* ── Sidebar overlay (mobile) ── */
    .sidebar-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 45;
      opacity: 0;
      visibility: hidden;
      transition: opacity .3s ease, visibility .3s ease;
    }
    .sidebar-overlay.open { opacity: 1; visibility: visible; }

    /* ── Bottom Nav (mobile) ── */
    .bottom-nav {
      display: none;
      position: fixed;
      left: 0; right: 0; bottom: 0;
      background: #fff;
      border-top: 1px solid var(--border);
      padding: 10px 8px calc(10px + env(safe-area-inset-bottom));
      z-index: 40;
      box-shadow: 0 -4px 20px rgba(15,23,42,0.06);
      justify-content: space-around;
      align-items: center;
    }
    .bn-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      color: var(--text-muted);
      font-size: 11px;
      font-weight: 500;
      padding: 4px 10px;
      border-radius: 10px;
      flex: 1;
      max-width: 80px;
      transition: color .15s;
    }
    .bn-item svg { width: 20px; height: 20px; stroke-width: 1.8; }
    .bn-item.active { color: var(--green); }
    .bn-icon {
      width: 36px; height: 36px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s, color .2s;
    }
    .bn-item.active .bn-icon {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
      box-shadow: 0 4px 12px rgba(13,122,82,0.3);
    }

    /* ── Responsive ── */
    @media (max-width: 1100px) {
      .records-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
      .app { grid-template-columns: 1fr; }
      .sidebar {
        position: fixed; left: 0; top: 0;
        width: 80%;
        max-width: 320px;
        height: 100vh;
        z-index: 50;
        transform: translateX(-100%);
        transition: transform .3s ease;
        border-top-right-radius: 28px;
        border-bottom-right-radius: 28px;
        border-right: none;
        box-shadow: 8px 0 30px rgba(15,23,42,0.15);
        padding: 18px 14px 20px;
      }
      .sidebar.open { transform: translateX(0); }
      .hamburger { display: inline-flex; }
      .topbar { padding: 14px 18px; }
      .content { padding: 18px 18px 32px; }
      .section-heading { font-size: 18px; letter-spacing: 2px; }
      .records-grid { grid-template-columns: 1fr; gap: 16px; }
      .bottom-nav { display: flex; }
      body { padding-bottom: 78px; }
    }
    @media (max-width: 520px) {
      html, body, .app, .main { max-width: 100%; overflow-x: hidden; }
      .content { padding: 14px 14px 28px; }
      .page-title { font-size: 18px; }
      .record-card { padding: 18px; }
    }
  </style>
</head>
<body>

<div class="app">

  {{-- ── Sidebar ── --}}
  @include('member-portal.partials.sidebar', ['active' => 'records', 'mode' => 'links'])

  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  {{-- ── Main ── --}}
  <div class="main">

    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <h1 class="page-title">ISGH Records</h1>
      </div>
      <div class="topbar-right">
        <span class="user-name">{{ ($profile->fullName ?? null) ?: 'Member' }}</span>
      </div>
    </header>

    <section class="content">
      <h2 class="section-heading">DOCUMENTS</h2>

      <div class="records-grid">

        {{-- Shura MOM --}}
        <div class="record-card">
          <div class="record-icon ic-peach">
            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/>
            </svg>
          </div>
          <div class="record-title">Shura MOM</div>
          <div class="record-sub">Minutes of Meeting</div>
          <div class="record-foot">
            <span class="pdf-badge">ZIP</span>
            <a href="{{ asset('documents/Meeting Minutes 2024-2026-20260507T123423Z-3-001.zip') }}" class="open-link" target="_blank" rel="noopener">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
              </svg>
              Open
            </a>
          </div>
        </div>

        {{-- ISGH Policy Manual --}}
        <div class="record-card">
          <div class="record-icon ic-blue">
            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
          </div>
          <div class="record-title">ISGH Policy Manual</div>
          <div class="record-sub">Official policy document</div>
          <div class="record-foot">
            <span class="pdf-badge">PDF</span>
            <a href="{{ asset('documents/ISGH_Policies_Procedures Manual_Oct2024.pdf') }}" class="open-link" target="_blank" rel="noopener">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
              </svg>
              Open
            </a>
          </div>
        </div>

        {{-- ISGH Constitution & Bylaws --}}
        <div class="record-card">
          <div class="record-icon ic-green">
            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <line x1="12" y1="3" x2="12" y2="21"/><path d="M5 7h14"/><path d="M5 7l-3 7h6z"/><path d="M19 7l-3 7h6z"/><path d="M8 21h8"/>
            </svg>
          </div>
          <div class="record-title">ISGH Constitution &amp; Bylaws</div>
          <div class="record-sub">Governing document</div>
          <div class="record-foot">
            <span class="pdf-badge">PDF</span>
            <a href="{{ asset('documents/Constitution and Bylaws as of 5 March 2023.pdf') }}" class="open-link" target="_blank" rel="noopener">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
              </svg>
              Open
            </a>
          </div>
        </div>

      </div>
    </section>
  </div>
</div>

{{-- ── Bottom Nav (mobile) ── --}}
@include('member-portal.partials.bottom-nav', ['active' => 'records', 'mode' => 'links'])

<script>
  (function () {
    const app     = document.querySelector('.app');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    const isMobile = () => window.innerWidth <= 768;

    function openMobileDrawer()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function closeMobileDrawer() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }
    function toggleDesktopCollapse() { app.classList.toggle('sidebar-collapsed'); }

    window.toggleSidebar = function () {
      if (isMobile()) {
        sidebar.classList.contains('open') ? closeMobileDrawer() : openMobileDrawer();
      } else {
        toggleDesktopCollapse();
      }
    };

    overlay.addEventListener('click', closeMobileDrawer);

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        if (sidebar.classList.contains('open')) closeMobileDrawer();
        else if (app.classList.contains('sidebar-collapsed')) app.classList.remove('sidebar-collapsed');
      }
    });

    window.addEventListener('resize', () => {
      if (!isMobile() && sidebar.classList.contains('open')) closeMobileDrawer();
      if (isMobile() && app.classList.contains('sidebar-collapsed')) app.classList.remove('sidebar-collapsed');
    });

    sidebar.querySelectorAll('.nav-item').forEach(link => {
      link.addEventListener('click', () => { if (isMobile()) closeMobileDrawer(); });
    });
  })();
</script>
</body>
</html>

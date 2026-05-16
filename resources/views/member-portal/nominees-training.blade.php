<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Nominees Training &amp; Orientation — ISGH</title>
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
    .page-title { font-size: 20px; font-weight: 700; color: var(--text); letter-spacing: -0.3px; }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .user-name { font-size: 14px; font-weight: 600; color: var(--text); }

    /* ── Content ── */
    .content {
      padding: 26px 32px 40px;
      display: flex;
      flex-direction: column;
      gap: 22px;
    }

    /* ── Action Required banner ── */
    .alert-banner {
      background: linear-gradient(135deg, #fdf1dc 0%, #fcf6e9 100%);
      border: 1px solid #f3dca5;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 22px 26px;
    }
    .alert-head {
      display: flex;
      align-items: center;
      gap: 9px;
      font-size: 16px;
      font-weight: 700;
      color: #8a5a17;
      margin-bottom: 8px;
    }
    .alert-head svg { width: 18px; height: 18px; stroke-width: 2; }
    .alert-text {
      font-size: 13.5px;
      color: #946c2c;
      max-width: 720px;
      margin-bottom: 14px;
    }
    .deadline-chip {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: #f6c969;
      color: #6b4513;
      font-size: 12.5px;
      font-weight: 700;
      padding: 7px 14px;
      border-radius: 9px;
    }
    .deadline-chip svg { width: 14px; height: 14px; stroke-width: 2; }

    /* ── Orientation card ── */
    .orientation-card {
      background: var(--surface);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 24px 26px;
    }
    .orientation-title { font-size: 18px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }
    .orientation-sub {
      font-size: 13px;
      color: var(--text-muted);
      margin: 6px 0 18px;
    }
    .orientation-body {
      display: grid;
      grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
      gap: 20px;
      align-items: stretch;
    }
    .video-wrap {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      border-radius: var(--radius-sm);
      overflow: hidden;
      background: #000;
    }
    .video-wrap iframe {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }
    .warning-box {
      background: #fdeceb;
      border: 1px solid #f6cdca;
      border-radius: var(--radius-sm);
      padding: 22px 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      gap: 12px;
    }
    .warning-icon {
      width: 44px; height: 44px;
      color: #d64545;
    }
    .warning-icon svg { width: 44px; height: 44px; stroke-width: 1.7; }
    .warning-text {
      font-size: 12.5px;
      color: #b3413c;
      line-height: 1.6;
    }

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
    @media (max-width: 960px) {
      .orientation-body { grid-template-columns: 1fr; }
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
      .content { padding: 18px 18px 32px; gap: 16px; }
      .alert-banner { padding: 18px; }
      .orientation-card { padding: 18px; }
      .bottom-nav { display: flex; }
      body { padding-bottom: 78px; }
    }
    @media (max-width: 520px) {
      html, body, .app, .main { max-width: 100%; overflow-x: hidden; }
      .content { padding: 14px 14px 28px; }
      .page-title { font-size: 18px; }
      .deadline-chip { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

<div class="app">

  {{-- ── Sidebar ── --}}
  @include('member-portal.partials.sidebar', ['active' => 'nominees-training', 'mode' => 'links'])

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
        <h1 class="page-title">Nominees Training &amp; Orientation</h1>
      </div>
      <div class="topbar-right">
        <span class="user-name">{{ ($profile->fullName ?? null) ?: 'Member' }}</span>
      </div>
    </header>

    <section class="content">

      {{-- Action Required banner --}}
      <div class="alert-banner">
        <div class="alert-head">
          <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          Action Required — ISGH 2025 Elections
        </div>
        <p class="alert-text">
          Thank you for taking part in the ISGH 2025 Elections. To understand the responsibilities and
          expectations for your position of nomination, please watch the orientation video below and
          complete the Acknowledgment form.
        </p>
        <span class="deadline-chip">
          <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          Deadline: Sunday, November 09th - Noon
        </span>
      </div>

      {{-- Orientation card --}}
      <div class="orientation-card">
        <div class="orientation-title">ISGH Election Nominee's Orientation</div>
        <p class="orientation-sub">
          This orientation is mandatory per Bylaws Article VII, Section 4 (H) passed September 2022
          for ALL nominees and candidates seeking reelection.
        </p>

        <div class="orientation-body">
          <div class="video-wrap">
            {{-- Placeholder YouTube embed --}}
            <iframe
              src="https://www.youtube.com/embed/dQw4w9WgXcQ"
              title="ISGH Election Nominee's Orientation"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
              loading="lazy"></iframe>
          </div>

          <div class="warning-box">
            <span class="warning-icon">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </span>
            <p class="warning-text">
              Any candidate who fails to complete the orientation without a valid reason shall be
              declared unqualified by the Election Commission and will not appear on the Final
              Nominations List or ballot. If you attended the live session and filled out the form,
              you may ignore this.
            </p>
          </div>
        </div>
      </div>

    </section>
  </div>
</div>

{{-- ── Bottom Nav (mobile) ── --}}
@include('member-portal.partials.bottom-nav', ['active' => 'nominees-training', 'mode' => 'links'])

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

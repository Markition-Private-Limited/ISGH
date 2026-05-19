{{-- ============================================================
     App Layout â€” ISGH Staff Portal (Dashboard Shell)
     resources/views/layouts/app.blade.php
     ============================================================ --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Dashboard') - ISGH Staff Portal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
  @stack('styles')
</head>
<body>

<div class="app-shell" id="app-shell">

  {{-- Mobile dim overlay --}}
  <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

  {{-- SIDEBAR --}}
  <aside class="sidebar" id="app-sidebar" role="navigation" aria-label="Main navigation">

    {{-- Brand bar --}}
    <div class="sidebar-brand">
      <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="sidebar-brand-logo" />

      <div class="sidebar-brand-text sidebar-text">
        <span class="sidebar-brand-name">ISGH</span>
        <span class="sidebar-brand-role">Staff Portal</span>
      </div>

      {{-- Desktop collapse / expand toggle --}}
      <button
        class="sidebar-collapse-btn"
        id="sidebar-collapse-btn"
        title="Toggle sidebar"
        aria-label="Toggle sidebar"
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="3" y1="6"  x2="21" y2="6"/>
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>

      <button
        class="sidebar-mobile-close"
        id="sidebar-mobile-close"
        type="button"
        aria-label="Close navigation"
      >
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    {{-- Section label --}}
    <div class="sidebar-section-label sidebar-text">Modules</div>

    {{-- Nav links --}}
    <nav class="sidebar-nav" aria-label="Portal modules">

      <a
        href="{{ route('portal.dashboard') }}"
        class="sidebar-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}"
        aria-current="{{ request()->routeIs('portal.dashboard') ? 'page' : 'false' }}"
        title="Dashboard"
      >
        <span class="sidebar-link-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M3 11.5 12 4l9 7.5"/>
            <path d="M5 10.5V20h14v-9.5"/>
            <path d="M9 20v-6h6v6"/>
          </svg>
        </span>
        <span class="sidebar-link-text sidebar-text">Dashboard</span>
      </a>

      <a
        href="{{ route('portal.members') }}"
        class="sidebar-link {{ request()->routeIs('portal.members*') ? 'active' : '' }}"
        aria-current="{{ request()->routeIs('portal.members*') ? 'page' : 'false' }}"
        title="Members"
      >
        <span class="sidebar-link-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </span>
        <span class="sidebar-link-text sidebar-text">Members</span>
      </a>

    </nav>

    <div class="sidebar-footer">
      <button class="sidebar-logout-btn" type="button" onclick="showLogoutModal()">
        <span class="sidebar-logout-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M10 17l5-5-5-5"/>
            <path d="M15 12H3"/>
            <path d="M21 19V5a2 2 0 0 0-2-2h-6"/>
            <path d="M13 21h6a2 2 0 0 0 2-2"/>
          </svg>
        </span>
        <span>Logout</span>
      </button>
    </div>

  </aside>

  {{-- MAIN CONTENT --}}
  <div class="main-content" id="main-content-wrap">

    {{-- Topbar --}}
    <header class="topbar" role="banner">
      <div class="topbar-left" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div class="topbar-mobile-brand" aria-label="ISGH Staff Portal">
          <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" />
          <span>ISGH</span>
        </div>

        {{-- Mobile hamburger (hidden on desktop, shown on â‰¤768px) --}}
        <button
          class="sidebar-toggle-btn"
          id="sidebar-toggle-btn"
          aria-label="Open navigation"
          aria-controls="app-sidebar"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>

        <h1 class="topbar-title">@yield('page-title', 'Dashboard')</h1>
      </div>

      <div class="topbar-right">
        <span class="mobile-page-label">@yield('title', 'Dashboard')</span>
        <div class="role-dropdown">
          <button
            class="role-dropdown-btn"
            data-dropdown="role-menu"
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="role-menu"
          >
            <span>@yield('user-role', 'President â€“ Executive Board')</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

          <div class="role-dropdown-menu" id="role-menu" role="menu" aria-label="User menu">
            <hr style="border:none;border-top:1px solid var(--clr-border-light);margin:4px 0;" />
            <a class="role-dropdown-item" href="#" role="menuitem"
               onclick="event.preventDefault();showLogoutModal();">
              Sign Out
            </a>
          </div>
        </div>
      </div>
    </header>

    <form id="logout-form" method="POST" action="{{ route('portal.logout') }}" style="display:none;">
      @csrf
    </form>

    {{-- Flash messages --}}
    @if (session('success') || session('error') || session('info'))
      <div style="padding:var(--sp-4) var(--sp-6) 0;">
        @if(session('success'))
          <div class="alert alert-success" data-auto-hide="5000" role="alert">{{ session('success') }}</div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger"  data-auto-hide="5000" role="alert">{{ session('error') }}</div>
        @endif
        @if(session('info'))
          <div class="alert alert-warning" data-auto-hide="5000" role="alert">{{ session('info') }}</div>
        @endif
      </div>
    @endif

    <main class="page-body" role="main">
      @yield('content')
    </main>

  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')

{{-- ── Logout Confirmation Modal ───────────────────────────────── --}}
<div id="logout-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
  {{-- Backdrop --}}
  <div onclick="hideLogoutModal()" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(2px);"></div>
  {{-- Dialog --}}
  <div style="position:relative;background:#fff;border-radius:16px;padding:32px 28px 24px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.2);text-align:center;">
    {{-- Icon --}}
    <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 17l5-5-5-5"/>
        <path d="M15 12H3"/>
        <path d="M21 19V5a2 2 0 0 0-2-2h-6"/>
        <path d="M13 21h6a2 2 0 0 0 2-2"/>
      </svg>
    </div>
    <div style="font-size:1.15rem;font-weight:700;color:#111827;margin-bottom:6px;">Confirm Logout</div>
    <div style="font-size:.875rem;color:#6b7280;margin-bottom:24px;">Are you sure you want to logout?</div>
    <div style="display:flex;gap:12px;">
      <button onclick="hideLogoutModal()" style="flex:1;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.875rem;font-weight:600;color:#374151;cursor:pointer;">
        No
      </button>
      <button onclick="document.getElementById('logout-form').submit()" style="flex:1;padding:10px;border:none;border-radius:8px;background:#ef4444;font-size:.875rem;font-weight:600;color:#fff;cursor:pointer;">
        Yes, Logout
      </button>
    </div>
  </div>
</div>

<script>
function showLogoutModal() {
  var m = document.getElementById('logout-modal');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function hideLogoutModal() {
  var m = document.getElementById('logout-modal');
  m.style.display = 'none';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') hideLogoutModal();
});
</script>
</body>
</html>

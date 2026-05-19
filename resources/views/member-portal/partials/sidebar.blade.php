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
    <form method="POST" action="{{ route('member-portal.logout') }}" class="nav-logout-form" id="memberLogoutForm">
      @csrf
      <button type="button" class="nav-item nav-logout" onclick="showMemberLogoutModal()">
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Logout
      </button>
    </form>
  </nav>
</aside>

{{-- ── Logout Confirmation Modal ──────────────────────────────────
     Lives in the sidebar partial so it is present on every member-portal
     page. The sidebar logout button opens this instead of submitting. --}}
<div id="memberLogoutModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
  {{-- Backdrop --}}
  <div onclick="hideMemberLogoutModal()" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(2px);"></div>
  {{-- Dialog --}}
  <div role="dialog" aria-modal="true" aria-labelledby="memberLogoutModalTitle"
       style="position:relative;background:#fff;border-radius:16px;padding:32px 28px 24px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.2);text-align:center;">
    <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </div>
    <div id="memberLogoutModalTitle" style="font-size:1.15rem;font-weight:700;color:#111827;margin-bottom:6px;">Confirm Logout</div>
    <div style="font-size:.875rem;color:#6b7280;margin-bottom:24px;">Are you sure you want to log out?</div>
    <div style="display:flex;gap:12px;">
      <button type="button" onclick="hideMemberLogoutModal()"
              style="flex:1;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.875rem;font-weight:600;color:#374151;cursor:pointer;">
        No
      </button>
      <button type="button" onclick="document.getElementById('memberLogoutForm').submit()"
              style="flex:1;padding:10px;border:none;border-radius:8px;background:#ef4444;font-size:.875rem;font-weight:600;color:#fff;cursor:pointer;">
        Yes
      </button>
    </div>
  </div>
</div>

<script>
  function showMemberLogoutModal() {
    document.getElementById('memberLogoutModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
  function hideMemberLogoutModal() {
    document.getElementById('memberLogoutModal').style.display = 'none';
    document.body.style.overflow = '';
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') hideMemberLogoutModal();
  });
</script>

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

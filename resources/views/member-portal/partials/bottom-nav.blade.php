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

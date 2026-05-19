{{-- ============================================================
     Members Page — ISGH Staff Portal
     resources/views/portal/members/index.blade.php
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Members')
@section('page-title', 'Members (' . number_format($totalCount ?? 33908) . ')')
@section('user-role', auth()->user()->roleLabel())

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<style>
  /* Match select2 to the portal design system */
  .select2-container--default .select2-selection--single {
    height: 36px;
    border: 1px solid var(--clr-border, #d1d5db);
    border-radius: 6px;
    background: var(--clr-surface, #fff);
    font-size: .85rem;
    color: var(--clr-text-1, #111827);
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 34px;
    padding-left: 10px;
    color: var(--clr-text-1, #111827);
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px;
  }
  .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--clr-primary, #2563eb);
  }
  .select2-dropdown {
    border: 1px solid var(--clr-border, #d1d5db);
    border-radius: 6px;
    font-size: .85rem;
    z-index: 9999;
  }
  .select2-search--dropdown .select2-search__field {
    border: 1px solid var(--clr-border, #d1d5db);
    border-radius: 4px;
    padding: 4px 8px;
    font-size: .85rem;
  }
  /* Make select2 fill the filter-select-wrap width */
  .filter-select-wrap .select2-container {
    width: 100% !important;
  }
</style>
@endpush

@section('content')

  {{-- ── Filters Card ──────────────────────────────────────── --}}
  <div class="filters-card">
    <div class="filters-title">Filters</div>

    <form id="members-filter-form" method="GET" action="{{ route('portal.members') }}">

      {{-- Search --}}
      <div class="filters-search">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          name="search"
          class="form-control"
          placeholder="Search by name or email"
          value="{{ request('search') }}"
          aria-label="Search members"
        />
      </div>

      {{-- Filter Row --}}
      <div class="filters-row">

        @if (auth()->user()->isCityWide())
        <div class="filter-select-wrap">
          <label class="filter-label" for="filter-zone">Zone</label>
          <select id="filter-zone" name="zone" class="filter-select" aria-label="Filter by zone">
            <option value="">All Zones</option>
            <option value="north"     {{ request('zone') === 'north'     ? 'selected' : '' }}>North Zone</option>
            <option value="northwest" {{ request('zone') === 'northwest' ? 'selected' : '' }}>Northwest Zone</option>
            <option value="south"     {{ request('zone') === 'south'     ? 'selected' : '' }}>South Zone</option>
            <option value="southeast" {{ request('zone') === 'southeast' ? 'selected' : '' }}>Southeast Zone</option>
            <option value="southwest" {{ request('zone') === 'southwest' ? 'selected' : '' }}>Southwest Zone</option>
          </select>
        </div>
        @endif

        @if (!auth()->user()->isCenterLevel())
        <div class="filter-select-wrap">
          <label class="filter-label" for="filter-masjid">Masjid</label>
          <select id="filter-masjid" name="center" class="filter-select" aria-label="Filter by masjid">
            <option value="">All Masjid</option>
            @foreach ($masjids ?? [] as $masjid)
              <option value="{{ $masjid['value'] }}" {{ request('center') === $masjid['value'] ? 'selected' : '' }}>
                {{ $masjid['name'] }}
              </option>
            @endforeach
          </select>
        </div>
        @endif

        <div class="filter-select-wrap">
          <label class="filter-label" for="filter-zip">Zip Code</label>
          <select id="filter-zip" name="zip" class="filter-select" aria-label="Filter by zip code">
            <option value="">All Zip Codes</option>
            @foreach ($zipCodes ?? [] as $zip)
              <option value="{{ $zip }}" {{ request('zip') === $zip ? 'selected' : '' }}>{{ $zip }}</option>
            @endforeach
          </select>
        </div>

        <div class="filter-select-wrap">
          <label class="filter-label" for="filter-status">Status</label>
          <select id="filter-status" name="status" class="filter-select" aria-label="Filter by status">
            <option value="">All Status</option>
            <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Active</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
            <option value="lapsed"  {{ request('status') === 'lapsed'  ? 'selected' : '' }}>Lapsed</option>
          </select>
        </div>

        <div class="filter-select-wrap">
          <label class="filter-label" for="filter-type">Membership Type</label>
          <select id="filter-type" name="type" class="filter-select" aria-label="Filter by membership type">
            <option value="">All Types</option>
            <option value="individual"  {{ request('type') === 'individual'  ? 'selected' : '' }}>Individual</option>
            <option value="checkmatic"  {{ request('type') === 'checkmatic'  ? 'selected' : '' }}>Checkmatic</option>
            <option value="lifetime"    {{ request('type') === 'lifetime'    ? 'selected' : '' }}>Lifetime</option>
          </select>
        </div>

      </div>

      {{-- Hidden level param — passed through from dashboard drill-down links --}}
      @if (request('level'))
        <input type="hidden" name="level" value="{{ request('level') }}">
        <div style="margin-top:.5rem;font-size:.78rem;color:var(--clr-text-3);">
          Filtered by level: <strong style="color:var(--clr-text-1);">{{ request('level') }}</strong>
          <a href="{{ route('portal.members', array_diff_key(request()->query(), ['level' => ''])) }}" style="margin-left:.5rem;color:var(--clr-danger);text-decoration:none;" aria-label="Clear level filter">&#x2715;</a>
        </div>
      @endif

    </form>
  </div>

  {{-- ── Table Section ─────────────────────────────────────── --}}
  <div>

    {{-- Toolbar --}}
    <div class="table-toolbar">
      <div>
        <span class="table-toolbar-title">Members</span>
        <span class="table-toolbar-count">({{ number_format($filteredCount ?? 27) }})</span>
      </div>
      <div class="toolbar-actions">
        <a
          href="{{ route('portal.members.export.csv', request()->query()) }}"
          class="btn btn-secondary btn-sm"
          aria-label="Export members to CSV"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          CSV
        </a>
        <a
          href="{{ route('portal.members.print', request()->query()) }}"
          target="_blank"
          rel="noopener"
          class="btn btn-secondary btn-sm"
          aria-label="Open printable members list in a new tab"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="6 9 6 2 18 2 18 9"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
          Print
        </a>
      </div>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
      <table class="data-table" aria-label="Members list">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Member Name</th>
            <th scope="col">Membership Type</th>
            <th scope="col">Zone / Masjid</th>
            <th scope="col">Complete Address</th>
            <th scope="col">Zip Code</th>
            <th scope="col">Dates</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>

          @php
            /* Fallback demo rows if $members is not provided by controller */
            $members = $members ?? collect(array_fill(0, 10, [
              'id'            => 1,
              'name'          => 'Ahmed Hassaan',
              'type'          => 'Individual Membership',
              'type_sub'      => 'Individual',
              'zone'          => 'North Zone',
              'masjid'        => 'Addr/Road/Bloc',
              'address'       => '1234 Westheimer Rd Houston',
              'zip'           => '77027',
              'joined'        => '23/01/2020',
              'renewal'       => '23/01/2030',
              'status'        => 'Active',
            ]));
          @endphp

          @forelse ($members as $i => $member)
            @php
              $status     = is_array($member) ? ($member['status'] ?? 'Active') : ($member->status ?? 'Active');
              $badgeClass = match(strtolower($status)) {
                'active'  => 'badge-active',
                'pending' => 'badge-pending',
                'expired', 'expire' => 'badge-expired',
                'lapsed'  => 'badge-lapsed',
                default   => 'badge-lapsed',
              };
              $name    = is_array($member) ? ($member['name']    ?? '—') : (($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));
              $type    = is_array($member) ? ($member['type']    ?? '—') : ($member->membership_type ?? '—');
              $typeSub = is_array($member) ? ($member['type_sub'] ?? '') : ($member->type_sub ?? '');
              $zone    = is_array($member) ? ($member['zone']    ?? '—') : ($member->zone ?? '—');
              $masjid  = is_array($member) ? ($member['masjid']  ?? '')  : ($member->masjid ?? '');
              $address = is_array($member) ? ($member['address'] ?? '—') : ($member->address ?? '—');
              $zip     = is_array($member) ? ($member['zip']     ?? '—') : ($member->zip ?? '—');
              $joined  = is_array($member) ? ($member['joined']  ?? '—') : ($member->joined_at ?? '—');
              $renewal = is_array($member) ? ($member['renewal'] ?? '—') : ($member->renewal_date ?? '—');
            @endphp
            <tr>
              <td class="col-num">{{ $i + 1 }}</td>

              <td>
                <div class="td-name">{{ $name }}</div>
              </td>

              <td>
                <div style="font-size:.82rem;font-weight:600;">{{ $type }}</div>
                @if ($typeSub)
                  <div class="td-meta">{{ $typeSub }}</div>
                @endif
              </td>

              <td>
                <div style="font-size:.82rem;font-weight:500;">{{ $zone }}</div>
                @if ($masjid)
                  <div class="td-address-icon" style="margin-top:2px;">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                      <circle cx="12" cy="10" r="3"/>
                    </svg>
                    {{ $masjid }}
                  </div>
                @endif
              </td>

              <td>
                <div class="td-address-icon">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                  </svg>
                  {{ $address }}
                </div>
              </td>

              <td style="font-weight:600;">{{ $zip }}</td>

              <td>
                <div style="font-size:.75rem;color:var(--clr-text-3);">
                  <strong style="color:var(--clr-text-2);">Joined:</strong> {{ $joined }}
                </div>
                <div style="font-size:.75rem;color:var(--clr-text-3);margin-top:2px;">
                  <strong style="color:var(--clr-text-2);">Renewal:</strong> {{ $renewal }}
                </div>
              </td>

              <td>
                <span class="badge {{ $badgeClass }}">{{ $status }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--clr-text-3);">
                No members found matching your filters.
              </td>
            </tr>
          @endforelse

        </tbody>
      </table>

      {{-- Pagination --}}
      @php
        $isPaginator = method_exists($members, 'firstItem');
        $showingFrom = $isPaginator ? $members->firstItem() : 1;
        $showingTo   = $isPaginator ? $members->lastItem()  : $members->count();
        $lastPage    = $isPaginator ? $members->lastPage()  : 1;
        $currentPage = $isPaginator ? $members->currentPage() : 1;
        $onFirstPage = $isPaginator ? $members->onFirstPage() : true;
        $hasMore     = $isPaginator ? $members->hasMorePages() : false;
      @endphp
      <div class="pagination-wrap">
        <span class="pagination-info">
          Showing {{ $showingFrom }} to {{ $showingTo }}
          of {{ number_format($filteredCount ?? 27) }} members
        </span>

        <div class="pagination-pages" role="navigation" aria-label="Pagination">
          {{-- Previous --}}
          @if ($onFirstPage)
            <span class="page-btn disabled" aria-disabled="true" aria-label="Previous page">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
              Prev
            </span>
          @else
            <a href="{{ $members->previousPageUrl() }}" class="page-btn" aria-label="Previous page">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
              Prev
            </a>
          @endif

          {{-- Page numbers --}}
          @for ($p = 1; $p <= min($lastPage, 5); $p++)
            @if ($p === $currentPage)
              <span class="page-btn active" aria-current="page">{{ $p }}</span>
            @else
              <a href="{{ $isPaginator ? $members->url($p) : '#' }}"
                 class="page-btn" aria-label="Page {{ $p }}">{{ $p }}</a>
            @endif
          @endfor

          {{-- Next --}}
          @if ($hasMore)
            <a href="{{ $members->nextPageUrl() }}" class="page-btn" aria-label="Next page">
              Next
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          @else
            <span class="page-btn disabled" aria-disabled="true" aria-label="Next page">
              Next
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
          @endif
        </div>
      </div>

    </div>
    {{-- /table-wrap --}}

  </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  const form     = document.getElementById('members-filter-form');
  const zoneEl   = document.getElementById('filter-zone');
  const masjidEl = document.getElementById('filter-masjid');
  const zipEl    = document.getElementById('filter-zip');

  // Initialise Select2 on the masjid and zip dropdowns (cosmetic only).
  ['#filter-masjid', '#filter-zip'].forEach(function (sel) {
    const el = document.querySelector(sel);
    if (!el) return;
    $(el).select2({
      placeholder: el.options[0].text,
      allowClear: true,
      width: '100%',
    });
  });

  // Clear a dropdown's value and sync Select2's display.
  function clearSelect(el) {
    if (!el) return;
    el.value = '';
    $(el).trigger('change.select2');
  }

  // Cascading filters — every level auto-submits, and clears its now-stale
  // dependents first so a filter never outlives the parent it belonged to.
  // The reload re-renders Masjid (zone-narrowed) and ZIP (zone + masjid
  // narrowed) server-side, so the cascade survives reloads and URL edits.
  //
  //   Zone   change → clear Masjid + ZIP, then submit
  //   Masjid change → clear ZIP, then submit
  //   ZIP    change → submit

  if (zoneEl) {
    zoneEl.addEventListener('change', function () {
      clearSelect(masjidEl);
      clearSelect(zipEl);
      form.submit();
    });
  }

  if (masjidEl) {
    $(masjidEl).on('select2:select select2:clear', function () {
      clearSelect(zipEl);
      form.submit();
    });
  }

  if (zipEl) {
    $(zipEl).on('select2:select select2:clear', function () {
      form.submit();
    });
  }
})();
</script>
@endpush

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>ISGH Member Portal – Member Directory</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8.5px;
    color: #111;
    background: #fff;
  }

  /* ── Header ── */
  .header {
    padding: 18px 24px 10px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 10px;
  }
  .header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .header-title {
    font-size: 18px;
    font-weight: 700;
    color: #111;
    letter-spacing: -0.3px;
  }
  .header-subtitle {
    font-size: 9px;
    color: #6b7280;
    margin-top: 2px;
  }
  .role-badge {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 5px 12px;
    font-size: 8.5px;
    font-weight: 600;
    color: #374151;
  }

  /* ── Filters applied bar ── */
  .filters-bar {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 6px 12px;
    margin: 0 24px 10px;
    font-size: 7.5px;
    color: #6b7280;
  }
  .filters-bar strong { color: #374151; }

  /* ── Table ── */
  .table-wrap {
    padding: 0 24px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7.8px;
  }
  thead tr {
    background: #f3f4f6;
    border-bottom: 1px solid #d1d5db;
  }
  thead th {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 6.8px;
    font-weight: 700;
    color: #6b7280;
    padding: 6px 7px;
    text-align: left;
    white-space: nowrap;
  }
  tbody tr {
    border-bottom: 1px solid #f0f0f0;
  }
  tbody tr:nth-child(even) {
    background: #fafafa;
  }
  tbody td {
    padding: 5px 7px;
    vertical-align: top;
    color: #111827;
  }
  .col-num {
    color: #9ca3af;
    font-size: 7px;
    width: 20px;
  }
  .td-name {
    font-weight: 600;
    font-size: 8px;
  }
  .td-meta {
    font-size: 7px;
    color: #6b7280;
    margin-top: 1px;
  }
  .td-address {
    font-size: 7.5px;
    color: #374151;
  }
  .td-dates {
    font-size: 7px;
    color: #6b7280;
    line-height: 1.6;
  }
  .td-dates strong {
    color: #374151;
  }

  /* ── Status badges ── */
  .badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 20px;
    font-size: 7px;
    font-weight: 700;
    text-transform: capitalize;
    letter-spacing: 0.03em;
  }
  .badge-active  { background: #d1fae5; color: #065f46; }
  .badge-pending { background: #fef3c7; color: #92400e; }
  .badge-expired { background: #fee2e2; color: #991b1b; }
  .badge-lapsed  { background: #f3f4f6; color: #374151; }

  /* ── Pagination line ── */
  .page-line {
    margin: 6px 24px 0;
    text-align: center;
    font-size: 8px;
    color: #6b7280;
  }
  .page-line .page-box {
    display: inline-block;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 3px 10px;
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
  }

  /* ── Footer ── */
  .footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 6px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    font-size: 7px;
    color: #9ca3af;
  }
</style>
</head>
<body>

  {{-- Header --}}
  <div class="header">
    <div class="header-top">
      <div>
        <div class="header-title">ISGH Member Portal</div>
        <div class="header-subtitle">Member Directory &amp; Summary Report</div>
      </div>
      <div class="role-badge">{{ $userRole }}</div>
    </div>
  </div>

  {{-- Filters bar --}}
  <div class="filters-bar">
    <strong>Filters Applied:</strong>
    Zone: {{ $filterLabels['zone'] ?? 'All Zones' }} &nbsp;|&nbsp;
    Masjid: {{ $filterLabels['center'] ?? 'All Masjids' }} &nbsp;|&nbsp;
    Status: {{ $filterLabels['status'] ?? 'All Statuses' }} &nbsp;|&nbsp;
    Membership: {{ $filterLabels['type'] ?? 'All Types' }}
    @if (!empty($filterLabels['search']))
      &nbsp;|&nbsp; Search: &ldquo;{{ $filterLabels['search'] }}&rdquo;
    @endif
  </div>

  {{-- Table --}}
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th class="col-num">#</th>
          <th>Member Name</th>
          <th>Membership Type</th>
          <th>Zone / Masjid</th>
          <th>Complete Address</th>
          <th>ZIP Code</th>
          <th>Dates</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($members as $i => $member)
          @php
            $status     = $member['status'] ?? 'Active';
            $badgeClass = match(strtolower($status)) {
              'active'          => 'badge-active',
              'pending'         => 'badge-pending',
              'expired','expire'=> 'badge-expired',
              default           => 'badge-lapsed',
            };
          @endphp
          <tr>
            <td class="col-num">{{ $offset + $i + 1 }}</td>
            <td>
              <div class="td-name">{{ $member['name'] ?? '—' }}</div>
            </td>
            <td>
              <div style="font-weight:600;">{{ $member['type'] ?? '—' }}</div>
              @if (!empty($member['type_sub']))
                <div class="td-meta">{{ $member['type_sub'] }}</div>
              @endif
            </td>
            <td>
              <div style="font-weight:500;">{{ $member['zone'] ?? '—' }}</div>
              @if (!empty($member['masjid']))
                <div class="td-meta">{{ $member['masjid'] }}</div>
              @endif
            </td>
            <td class="td-address">{{ $member['address'] ?? '—' }}</td>
            <td style="font-weight:600;">{{ $member['zip'] ?? '—' }}</td>
            <td class="td-dates">
              <strong>Joined:</strong> {{ $member['joined'] ?? '—' }}<br/>
              <strong>Renewal:</strong> {{ $member['renewal'] ?? '—' }}
            </td>
            <td>
              <span class="badge {{ $badgeClass }}">{{ $status }}</span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center;padding:20px;color:#9ca3af;">
              No members found matching your filters.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    {{-- Page indicator --}}
    <div class="page-line" style="margin-top:10px;">
      <span class="page-box">Page &nbsp; {{ $currentPage }} &nbsp; / &nbsp; {{ $lastPage }}</span>
    </div>
  </div>

  {{-- Footer --}}
  <div class="footer">
    <span>Generated on: {{ now()->format('m-d-Y \a\t h:i A') }} | ISGH Member Portal</span>
    <span>Page {{ $currentPage }} of {{ $lastPage }}</span>
  </div>

</body>
</html>

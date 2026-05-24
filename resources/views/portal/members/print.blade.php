<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ISGH Admin Staff — Members List</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    color: #111827;
    background: #fff;
    padding: 28px 32px 48px;
    font-size: 13px;
  }

  /* ── Print toolbar (screen only) ── */
  .print-bar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 16px;
  }
  .print-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #0e7a52;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
  }
  .print-btn:hover { background: #0c6644; }

  /* ── Header ── */
  .report-head { text-align: center; margin-bottom: 18px; }
  .report-title {
    font-size: 26px;
    font-weight: 700;
    color: #0e7a52;
    letter-spacing: -0.3px;
  }
  .report-sub {
    font-size: 13px;
    color: #6b7280;
    margin-top: 4px;
  }
  .report-total {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
  }

  /* ── Applied-filters bar ── */
  .filters-bar {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 7px 12px;
    margin-bottom: 14px;
    font-size: 11px;
    color: #6b7280;
  }
  .filters-bar strong { color: #374151; }

  .rule {
    border: none;
    border-top: 2px solid #0e7a52;
    margin-bottom: 14px;
  }

  /* ── Table ── */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }
  thead tr {
    background: #f3f4f6;
    border-bottom: 1.5px solid #d1d5db;
  }
  thead th {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 10px;
    font-weight: 700;
    color: #6b7280;
    padding: 9px 10px;
    text-align: left;
    white-space: nowrap;
  }
  tbody tr { border-bottom: 1px solid #f0f0f0; }
  tbody tr:nth-child(even) { background: #fafafa; }
  tbody td {
    padding: 9px 10px;
    vertical-align: top;
    color: #111827;
  }
  .col-num { color: #9ca3af; font-size: 11px; width: 28px; }
  .td-name { font-weight: 700; }
  .td-meta { font-size: 11px; color: #6b7280; margin-top: 1px; }
  .td-address { color: #374151; }

  /* ── Status badges ── */
  .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: capitalize;
    letter-spacing: 0.03em;
  }
  .badge-active  { background: #d1fae5; color: #065f46; }
  .badge-pending { background: #fef3c7; color: #92400e; }
  .badge-expired { background: #fee2e2; color: #991b1b; }
  .badge-lapsed  { background: #f3f4f6; color: #374151; }

  /* ── Footer ── */
  .report-foot {
    margin-top: 22px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    font-size: 11px;
    color: #9ca3af;
  }

  .empty-row {
    text-align: center;
    padding: 28px;
    color: #9ca3af;
  }

  /* ── Print rules ── */
  @media print {
    body { padding: 0; }
    .print-bar { display: none; }
    thead { display: table-header-group; }
    tbody tr { page-break-inside: avoid; }
  }
</style>
</head>
<body>

  {{-- Print toolbar — screen only --}}
  <div class="print-bar">
    <button type="button" class="print-btn" onclick="window.print()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="6 9 6 2 18 2 18 9"/>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8"/>
      </svg>
      Print this page
    </button>
  </div>

  {{-- Header --}}
  <div class="report-head">
    <div class="report-title">ISGH Admin Staff</div>
    <div class="report-sub">Members List - {{ $generatedAt->format('m-d-Y') }}</div>
    <div class="report-total">Total Members: {{ number_format($total) }}</div>
  </div>

  {{-- Applied filters --}}
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

  <hr class="rule"/>

  {{-- Table --}}
  <table>
    <thead>
      <tr>
        <th class="col-num">#</th>
        <th>Member Name</th>
        <th>Membership Type</th>
        <th>Zone / Masjid</th>
        <th>Complete Address</th>
        <th>ZIP</th>
        <th>Dates</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($members as $i => $member)
        @php
          $status     = $member['status'] ?? 'Active';
          $badgeClass = match (strtolower($status)) {
            'active'           => 'badge-active',
            'pending'          => 'badge-pending',
            'expired', 'expire'=> 'badge-expired',
            default            => 'badge-lapsed',
          };
        @endphp
        <tr>
          <td class="col-num">{{ $i + 1 }}</td>
          <td><span class="td-name">{{ $member['name'] ?? '—' }}</span></td>
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
          <td class="td-meta">
            <strong style="color:#374151;">Joined:</strong> {{ $member['joined'] ?? '—' }}<br/>
            <strong style="color:#374151;">Renewal:</strong> {{ $member['renewal'] ?? '—' }}
          </td>
          <td><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="empty-row">No members found matching the current filters.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- Footer --}}
  <div class="report-foot">Generated by ISGH Admin Staff Dashboard</div>

  {{-- Open the browser print dialog automatically once the page has rendered.
       The "Print this page" button remains for reprinting after it's dismissed. --}}
  <script>
    window.addEventListener('load', function () {
      window.print();
    });
  </script>

</body>
</html>

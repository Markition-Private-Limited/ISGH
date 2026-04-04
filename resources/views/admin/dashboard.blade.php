<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — ISGH Memberships</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 0; color: #111; }
  header { background: #1a4a2e; color: #fff; padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; }
  header h1 { margin: 0; font-size: 1.1rem; font-weight: 700; }
  .container { max-width: 1200px; margin: 0 auto; padding: 1.5rem; }
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
  .stat { background: #fff; border-radius: 10px; padding: 1.2rem 1.5rem; box-shadow: 0 1px 6px rgba(0,0,0,0.08); }
  .stat .num { font-size: 2rem; font-weight: 700; }
  .stat .lbl { font-size: 13px; color: #6b7280; margin-top: 2px; }
  .stat.red .num { color: #dc2626; }
  .stat.green .num { color: #10b981; }
  .stat.blue .num { color: #3b82f6; }
  .toolbar { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
  .filter-btn { padding: 6px 16px; border-radius: 20px; border: 1px solid #d1d5db; background: #fff; font-size: 13px; cursor: pointer; text-decoration: none; color: #374151; }
  .filter-btn.active { background: #1a4a2e; color: #fff; border-color: #1a4a2e; }
  .retry-all-btn { margin-left: auto; padding: 8px 18px; background: #dc2626; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
  table { width: 100%; background: #fff; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.08); border-collapse: collapse; overflow: hidden; }
  th { background: #f9fafb; padding: 10px 14px; text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb; }
  td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; font-size: 13px; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #fafafa; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
  .badge-green  { background: #dcfce7; color: #166534; }
  .badge-red    { background: #fee2e2; color: #991b1b; }
  .badge-orange { background: #fff7ed; color: #9a3412; }
  .badge-gray   { background: #f3f4f6; color: #4b5563; }
  .step-pill { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; background: #fee2e2; color: #991b1b; }
  .btn-retry { padding: 5px 12px; background: #1a4a2e; color: #fff; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
  .btn-view  { padding: 5px 12px; background: #f3f4f6; color: #374151; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; }
  .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1rem; font-size: 14px; }
  .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .alert-info    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
  .pagination { display: flex; gap: 6px; margin-top: 1rem; justify-content: center; }
  .pagination a, .pagination span { padding: 6px 12px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 13px; text-decoration: none; color: #374151; }
  .pagination .active span { background: #1a4a2e; color: #fff; border-color: #1a4a2e; }
  @media(max-width:640px){ .stats { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<header>
  <h1>🏛 ISGH Membership Admin</h1>
  <form method="POST" action="{{ route('admin.login') }}" style="display:inline;">
    @csrf
    <input type="hidden" name="admin_token" value="">
    <button onclick="session.clear?.()" style="background:transparent;border:1px solid rgba(255,255,255,0.4);color:#fff;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer;"
      onclick="this.form.submit()">Logout</button>
  </form>
</header>

<div class="container">

  @if(session('success'))
    <div class="alert alert-success">✓ {{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-error">✗ {{ session('error') }}</div>
  @endif
  @if(session('info'))
    <div class="alert alert-info">ℹ {{ session('info') }}</div>
  @endif

  <!-- Stats -->
  <div class="stats">
    <div class="stat red">
      <div class="num">{{ $counts['failed'] }}</div>
      <div class="lbl">Failed / Pending WA (Stripe paid)</div>
    </div>
    <div class="stat green">
      <div class="num">{{ $counts['done'] }}</div>
      <div class="lbl">Fully Processed</div>
    </div>
    <div class="stat blue">
      <div class="num">{{ $counts['all'] }}</div>
      <div class="lbl">Total Registrations</div>
    </div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <a href="{{ route('admin.dashboard', ['filter' => 'failed']) }}" class="filter-btn {{ $filter === 'failed' ? 'active' : '' }}">
      ⚠ Failed ({{ $counts['failed'] }})
    </a>
    <a href="{{ route('admin.dashboard', ['filter' => 'done']) }}" class="filter-btn {{ $filter === 'done' ? 'active' : '' }}">
      ✓ Completed
    </a>
    <a href="{{ route('admin.dashboard', ['filter' => 'all']) }}" class="filter-btn {{ $filter === 'all' ? 'active' : '' }}">
      All
    </a>

    @if($counts['failed'] > 0)
    <form method="POST" action="{{ route('admin.retry-all') }}" style="margin-left:auto;"
          onsubmit="return confirm('Retry all {{ $counts['failed'] }} failed registrations?')">
      @csrf
      <button type="submit" class="retry-all-btn">⟳ Retry All Failed ({{ $counts['failed'] }})</button>
    </form>
    @endif
  </div>

  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Failed Step</th>
        <th>Retries</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($registrations as $reg)
      @php
        $primary = $reg->data['primary'] ?? [];
        $name    = trim(($primary['first_name'] ?? '') . ' ' . ($primary['last_name'] ?? ''));
        $color   = $reg->statusColor();
      @endphp
      <tr>
        <td style="font-family:monospace;font-size:11px;color:#6b7280;">{{ $reg->id }}</td>
        <td style="font-weight:600;">{{ $name ?: '—' }}</td>
        <td style="font-size:12px;color:#6b7280;">{{ $primary['email'] ?? '—' }}</td>
        <td><span style="font-size:12px;">{{ ucwords(str_replace('_',' ',$reg->data['type'] ?? '')) }}</span></td>
        <td>{{ $reg->data['amount_label'] ?? '—' }}</td>
        <td>
          <span class="badge badge-{{ $color }}">{{ $reg->statusLabel() }}</span>
        </td>
        <td>
          @if($reg->wa_step && $reg->wa_step !== 'done')
            <span class="step-pill">{{ $reg->wa_step }}</span>
          @else
            <span style="color:#d1d5db;">—</span>
          @endif
        </td>
        <td style="text-align:center;">{{ $reg->retry_count }}</td>
        <td style="font-size:11px;color:#6b7280;">{{ $reg->created_at->format('M d, Y H:i') }}</td>
        <td style="display:flex;gap:6px;align-items:center;">
          <a href="{{ route('admin.show', $reg) }}" class="btn-view">View</a>
          @if($reg->stripe_paid && !$reg->processed)
          <form method="POST" action="{{ route('admin.retry', $reg) }}"
                onsubmit="return confirm('Retry WA for {{ addslashes($name) }}?')">
            @csrf
            <button type="submit" class="btn-retry">⟳ Retry</button>
          </form>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="10" style="text-align:center;padding:2rem;color:#9ca3af;">
        @if($filter === 'failed') 🎉 No failed registrations. @else No records found. @endif
      </td></tr>
      @endforelse
    </tbody>
  </table>

  <!-- Pagination -->
  <div class="pagination">{{ $registrations->links() }}</div>

</div>
</body>
</html>

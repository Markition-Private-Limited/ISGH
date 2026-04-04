<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registration #{{ $registration->id }} — ISGH Admin</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; color: #111; }
  header { background: #1a4a2e; color: #fff; padding: 1rem 2rem; display: flex; align-items: center; gap: 1rem; }
  header a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; }
  header h1 { margin: 0; font-size: 1rem; font-weight: 700; }
  .container { max-width: 860px; margin: 0 auto; padding: 1.5rem; }
  .card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 6px rgba(0,0,0,0.08); margin-bottom: 1.25rem; }
  .card h2 { margin: 0 0 1rem; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
  .row { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.6rem; }
  .field { flex: 1; min-width: 160px; }
  .field .lbl { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; }
  .field .val { font-size: 14px; font-weight: 600; margin-top: 2px; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
  .badge-green  { background: #dcfce7; color: #166534; }
  .badge-red    { background: #fee2e2; color: #991b1b; }
  .badge-orange { background: #fff7ed; color: #9a3412; }
  .badge-gray   { background: #f3f4f6; color: #4b5563; }
  .error-box { background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem; margin-top: 1rem; }
  .error-box .step { font-size: 12px; font-weight: 700; color: #991b1b; margin-bottom: 4px; }
  .error-box pre { margin: 0; font-size: 12px; white-space: pre-wrap; word-break: break-all; color: #7f1d1d; }
  .steps { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 0.5rem; }
  .step-item { padding: 4px 12px; border-radius: 20px; font-size: 12px; background: #f3f4f6; color: #374151; }
  .step-item.done    { background: #dcfce7; color: #166534; }
  .step-item.current { background: #fee2e2; color: #991b1b; font-weight: 700; }
  .btn-retry { padding: 10px 22px; background: #1a4a2e; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
  .btn-back  { padding: 10px 18px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none; }
  .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1rem; font-size: 14px; }
  .alert-success { background: #dcfce7; color: #166534; }
  .alert-error   { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>

<header>
  <a href="{{ route('admin.dashboard') }}">← Dashboard</a>
  <h1>Registration #{{ $registration->id }}</h1>
</header>

<div class="container">

  @if(session('success'))
    <div class="alert alert-success">✓ {{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-error">✗ {{ session('error') }}</div>
  @endif

  @php
    $primary = $registration->data['primary'] ?? [];
    $color   = $registration->statusColor();
    $steps   = ['contact', 'invoice', 'payment', 'spouses', 'done'];
    $curStep = $registration->wa_step;
  @endphp

  <!-- Status -->
  <div class="card">
    <h2>Status</h2>
    <div class="row">
      <div class="field">
        <div class="lbl">Status</div>
        <div class="val"><span class="badge badge-{{ $color }}">{{ $registration->statusLabel() }}</span></div>
      </div>
      <div class="field">
        <div class="lbl">Stripe Paid</div>
        <div class="val">{{ $registration->stripe_paid ? '✓ Yes' : '✗ No' }}</div>
      </div>
      <div class="field">
        <div class="lbl">WA Contact ID</div>
        <div class="val">{{ $registration->wa_contact_id ?? '—' }}</div>
      </div>
      <div class="field">
        <div class="lbl">WA Invoice ID</div>
        <div class="val">{{ $registration->wa_invoice_id ?? '—' }}</div>
      </div>
      <div class="field">
        <div class="lbl">Retry Count</div>
        <div class="val">{{ $registration->retry_count }}</div>
      </div>
      <div class="field">
        <div class="lbl">Processed At</div>
        <div class="val" style="font-size:13px;">{{ $registration->processed_at?->format('M d, Y H:i') ?? '—' }}</div>
      </div>
    </div>

    <!-- Step progress -->
    <div class="lbl" style="margin-top:1rem;margin-bottom:6px;">WA Pipeline Steps</div>
    <div class="steps">
      @foreach($steps as $s)
        @php
          $isDone    = $curStep === 'done' || (in_array($s, $steps) && array_search($s,$steps) < array_search($curStep ?: 'contact',$steps) && $curStep !== null);
          $isCurrent = $curStep === $s && $curStep !== 'done';
          $cls = $isCurrent ? 'current' : ($isDone || ($registration->processed) ? 'done' : '');
        @endphp
        <span class="step-item {{ $cls }}">
          {{ $registration->processed && $s === 'done' ? '✓ ' : ($isCurrent ? '✗ ' : '') }}{{ ucfirst($s) }}
        </span>
      @endforeach
    </div>

    @if($registration->wa_error)
    <div class="error-box">
      <div class="step">Failed at step: {{ strtoupper($registration->wa_step ?? 'unknown') }} — {{ $registration->wa_error_at?->format('M d, Y H:i') }}</div>
      <pre>{{ $registration->wa_error }}</pre>
    </div>
    @endif
  </div>

  <!-- Member Info -->
  <div class="card">
    <h2>Member Information</h2>
    <div class="row">
      <div class="field"><div class="lbl">Name</div><div class="val">{{ $primary['first_name'] ?? '' }} {{ $primary['last_name'] ?? '' }}</div></div>
      <div class="field"><div class="lbl">Email</div><div class="val" style="font-size:13px;">{{ $primary['email'] ?? '—' }}</div></div>
      <div class="field"><div class="lbl">Phone</div><div class="val">{{ $primary['phone'] ?? '—' }}</div></div>
    </div>
    <div class="row">
      <div class="field"><div class="lbl">Membership Type</div><div class="val">{{ ucwords(str_replace('_',' ',$registration->data['type'] ?? '')) }}</div></div>
      <div class="field"><div class="lbl">Amount</div><div class="val">{{ $registration->data['amount_label'] ?? '—' }}</div></div>
      <div class="field"><div class="lbl">Zone / Center</div><div class="val">{{ $registration->data['zone'] ?? '—' }}</div></div>
    </div>
    <div class="row">
      <div class="field"><div class="lbl">Address</div><div class="val" style="font-size:13px;">{{ $primary['street'] ?? '' }}, {{ $primary['city'] ?? '' }}, {{ $primary['state'] ?? '' }} {{ $primary['zip'] ?? '' }}</div></div>
      <div class="field"><div class="lbl">Date of Birth</div><div class="val">{{ $primary['dob'] ?? '—' }}</div></div>
      <div class="field"><div class="lbl">TX DL / ID</div><div class="val">{{ $primary['tx_dl'] ?? '—' }}</div></div>
    </div>
  </div>

  <!-- Payment -->
  <div class="card">
    <h2>Stripe Reference</h2>
    <div class="row">
      <div class="field"><div class="lbl">Reference (UUID)</div><div class="val" style="font-family:monospace;font-size:12px;">{{ $registration->stripe_intent_id }}</div></div>
      <div class="field"><div class="lbl">Created</div><div class="val">{{ $registration->created_at->format('M d, Y H:i') }}</div></div>
    </div>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:10px;align-items:center;">
    <a href="{{ route('admin.dashboard') }}" class="btn-back">← Back</a>
    @if($registration->stripe_paid && !$registration->processed)
    @php
      $failedStep = $registration->wa_step ?? 'contact';
      $stepLabel  = ucfirst($failedStep === 'done' ? 'contact' : $failedStep);
    @endphp
    <form method="POST" action="{{ route('admin.retry', $registration) }}"
          onsubmit="return confirm('Resume Wild Apricot processing from step: {{ $stepLabel }}?')">
      @csrf
      <button type="submit" class="btn-retry">⟳ Resume from {{ $stepLabel }} step</button>
    </form>
    @endif
  </div>

</div>
</body>
</html>

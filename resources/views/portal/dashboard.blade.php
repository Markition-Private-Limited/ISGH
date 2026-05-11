{{-- ============================================================
     Dashboard Page — ISGH Staff Portal
     resources/views/portal/dashboard.blade.php
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Welcome, ' . auth()->user()->name)
@section('user-role', auth()->user()->name . ' – ' . auth()->user()->roleLabel())

@section('content')

  @if (!empty($warming))
  <div id="warming-banner" style="background:#f59e0b;color:#fff;border-radius:var(--r-md);padding:.65rem 1.2rem;margin-bottom:1.2rem;font-size:.85rem;display:flex;align-items:center;gap:.6rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;animation:spin 1.2s linear infinite;" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
    Dashboard statistics are being refreshed from WildApricot — this page will auto-update every 60 seconds until data is ready (takes ~5 minutes on first load).
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
  </div>
  <script>setTimeout(function(){ location.reload(); }, 60000);</script>
  @endif

  {{-- ── Stat Cards — desktop grid / mobile carousel ─────────── --}}
  <div class="stats-carousel-wrap mb-6">

    {{-- Desktop: plain grid (CSS handles layout) --}}
    {{-- Mobile:  horizontal scroll carousel with dots --}}
    <div class="stats-grid" id="stats-carousel-track">

      {{-- Total Members --}}
      <div class="stat-card" data-slide="0">
        <div class="stat-card-label">Total Members</div>
        <div class="stat-card-value">{{ number_format($stats['total'] ?? 55432) }}</div>
        <div class="stat-card-trend">
          <span class="stat-trend-up">▲ 3.1% this month</span>
        </div>
        <div class="stat-sparkline-wrap" aria-hidden="true">
          <canvas class="sparkline-canvas" id="spark-total" height="50"></canvas>
        </div>
      </div>

      {{-- Active Members --}}
      <div class="stat-card" data-slide="1">
        <div class="stat-card-label">Active Members</div>
        <div class="stat-card-value">{{ number_format($stats['active'] ?? 1986) }}</div>
        <div class="stat-card-trend">
          <span class="stat-trend-up">▲ 2.4% this month</span>
        </div>
        <div class="stat-sparkline-wrap" aria-hidden="true">
          <canvas class="sparkline-canvas" id="spark-active" height="50"></canvas>
        </div>
      </div>

      {{-- Lapsed Members --}}
      <div class="stat-card" data-slide="2">
        <div class="stat-card-label">Lapsed Members</div>
        <div class="stat-card-value" style="color:var(--clr-danger);">{{ number_format($stats['lapsed'] ?? 2) }}</div>
        <div class="stat-card-trend">
          <span class="stat-trend-down">▼ 0.5% this month</span>
        </div>
        <div class="stat-sparkline-wrap" aria-hidden="true">
          <canvas class="sparkline-canvas" id="spark-lapsed" height="50"></canvas>
        </div>
      </div>

    </div>

    {{-- Dots — only visible on mobile --}}
    <div class="carousel-dots" id="stats-carousel-dots" aria-label="Slide indicators">
      <button class="carousel-dot active" data-dot="0" aria-label="Slide 1"></button>
      <button class="carousel-dot"        data-dot="1" aria-label="Slide 2"></button>
      <button class="carousel-dot"        data-dot="2" aria-label="Slide 3"></button>
    </div>

  </div>

  {{-- ── Middle Row: Level Chart + Profile Status ──────────── --}}
  <div class="dashboard-grid mb-5">

    {{-- Members by Level Type --}}
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Members by Level Type</div>
          <div class="card-subtitle">Real-time breakdown of community enrollment by membership type</div>
        </div>
      </div>
      <div class="card-body" style="display:flex;align-items:center;gap:2.5rem;flex-wrap:wrap;">

        {{-- Legend table (left) --}}
        <div style="flex:1;min-width:180px;">
          <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--clr-text-3);margin-bottom:var(--sp-4);display:flex;justify-content:space-between;">
            <span>Category</span><span>Count (N)</span>
          </div>
          <table class="level-table" aria-label="Membership category breakdown">
            <tbody>
              <tr>
                <td>
                  <span class="level-dot" style="background:#1a5c42;" aria-hidden="true"></span>
                  Individual Membership
                </td>
                <td style="font-weight:700;text-align:right;padding-left:1rem;">
                  {{ number_format($levelBreakdown['individual'] ?? 31743) }}
                </td>
              </tr>
              <tr>
                <td>
                  <span class="level-dot" style="background:#f59e0b;" aria-hidden="true"></span>
                  Checkomatic Membership
                </td>
                <td style="font-weight:700;text-align:right;padding-left:1rem;">
                  {{ number_format($levelBreakdown['checkmatic'] ?? 54438) }}
                </td>
              </tr>
              <tr>
                <td>
                  <span class="level-dot" style="background:#3aab7b;" aria-hidden="true"></span>
                  Lifetime Membership
                </td>
                <td style="font-weight:700;text-align:right;padding-left:1rem;">
                  {{ number_format($levelBreakdown['lifetime'] ?? 52434) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {{-- Pie chart (right) --}}
        <div style="width:190px;height:190px;flex-shrink:0;position:relative;">
          <canvas id="membership-pie" aria-label="Membership type breakdown pie chart" role="img"></canvas>
        </div>

      </div>
    </div>

    {{-- Members Profiles Status --}}
    <div class="card profile-status-card" style="background:var(--clr-primary);color:#fff;">
      <div class="card-header" style="border-bottom:1px solid rgba(255,255,255,.12);">
        <div>
          <div class="card-title" style="color:#fff;">Members Profiles Status</div>
          <div class="card-subtitle" style="color:rgba(255,255,255,.5);">
            Live breakdown of active, pending, and incomplete member profiles
          </div>
        </div>
      </div>
      <div class="card-body">

        {{-- Progress bars --}}
        <div class="progress-bar-wrap">
          <div class="progress-item">
            <div class="progress-label-row">
              <span class="progress-label" style="color:rgba(255,255,255,.85);">Active</span>
              <span class="progress-value"  style="color:rgba(255,255,255,.55);">{{ number_format($profileStatus['active'] ?? 98539) }}</span>
            </div>
            <div class="progress-track" style="background:rgba(255,255,255,.15);">
              <div class="progress-fill" style="width:{{ $profileStatus['active_pct'] ?? 99 }}%;background:linear-gradient(90deg,#3aab7b,#6fcfa3);"></div>
            </div>
          </div>
          <div class="progress-item">
            <div class="progress-label-row">
              <span class="progress-label" style="color:rgba(255,255,255,.85);">Lapsed</span>
              <span class="progress-value"  style="color:rgba(255,255,255,.55);">{{ number_format($profileStatus['lapsed'] ?? 796) }}</span>
            </div>
            <div class="progress-track" style="background:rgba(255,255,255,.15);">
              <div class="progress-fill" style="width:{{ $profileStatus['lapsed_pct'] ?? 1 }}%;background:linear-gradient(90deg,#f59e0b,#fcd34d);"></div>
            </div>
          </div>
        </div>

        {{-- Profile donut --}}
        <div class="profile-donut-wrap" style="margin-top:var(--sp-5);display:flex;justify-content:center;">
          <div style="width:130px;height:130px;">
            <canvas id="profile-pie" aria-label="Profile status donut chart" role="img"></canvas>
          </div>
        </div>

      </div>
    </div>

  </div>

  {{-- ── Bottom Row: ZIP Table (left) + Zone Accordion (right) --}}
  <div class="dashboard-bottom-grid" style="display:grid;grid-template-columns:1fr 1.15fr;gap:var(--sp-5);align-items:start;">

    {{-- Members by ZIP Code --}}
    <div class="card zip-card">
      <div class="card-header">
        <div class="card-title">Members by ZIP Code</div>
      </div>

      <div class="zip-summary-card" style="background:var(--clr-primary);color:#fff;margin:var(--sp-4) var(--sp-5);border-radius:var(--r-md);padding:var(--sp-4) var(--sp-5);display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.5);">Total ZIP Codes</div>
          <div style="font-size:2rem;font-weight:800;line-height:1.1;">{{ $zipStats['total'] ?? 150 }}</div>
          <div style="font-size:.7rem;color:rgba(255,255,255,.5);margin-top:2px;">Across all city zones</div>
        </div>
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
      </div>

      <div class="zip-search">
        <svg aria-hidden="true" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="zip-search-input" placeholder="Search ZIP Code..." aria-label="Search ZIP codes" />
      </div>

      <div class="zip-table-scroll" style="overflow-x:auto;max-height:260px;overflow-y:auto;">
        <table class="data-table" aria-label="Members by ZIP code">
          <thead>
            <tr>
              <th>#</th><th>ZIP Code</th><th>City/Area</th><th>Members</th>
            </tr>
          </thead>
          <tbody id="zip-table-body">
            @php
              $zipData = $zipData ?? collect([
                ['zip'=>'78943','city'=>'River','count'=>4],
                ['zip'=>'78231','city'=>'San Antonio','count'=>3],
                ['zip'=>'77027','city'=>'Houston','count'=>6],
                ['zip'=>'77002','city'=>'Houston','count'=>5],
                ['zip'=>'78201','city'=>'San Antonio','count'=>4],
                ['zip'=>'77056','city'=>'Houston','count'=>9],
                ['zip'=>'78216','city'=>'San Antonio','count'=>4],
                ['zip'=>'77084','city'=>'Houston','count'=>7],
                ['zip'=>'78230','city'=>'San Antonio','count'=>4],
                ['zip'=>'77077','city'=>'Houston','count'=>4],
              ]);
            @endphp
            @foreach ($zipData as $i => $row)
              <tr>
                <td class="col-num">{{ $i + 1 }}</td>
                <td style="font-weight:600;">{{ $row['zip'] }}</td>
                <td class="text-muted">{{ $row['city'] }}</td>
                <td style="font-weight:700;">{{ $row['count'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="pagination-wrap" style="border-radius:0 0 var(--r-lg) var(--r-lg);">
        <span class="pagination-info">Showing 1-10 of {{ $zipStats['total'] ?? 150 }}</span>
        <div class="pagination-pages">
          <span class="page-btn disabled" aria-label="Previous">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
            Prev
          </span>
          <span class="page-btn active" aria-current="page">1</span>
          <a href="#" class="page-btn">2</a>
          <a href="#" class="page-btn">
            Next
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </a>
        </div>
      </div>
    </div>

    {{-- Members by Zone, Masjid & ZIP — Accordion ────────── --}}
    <div class="zone-dashboard-panel" style="background:transparent;">
      <h2 class="zone-dashboard-title" style="font-size:1.05rem;font-weight:700;color:var(--clr-text);margin-bottom:var(--sp-4);">
        Members by Zone, Masjid &amp; ZIP Code
      </h2>
      <div style="display:flex;flex-direction:column;gap:var(--sp-3);" id="zone-accordion">

        @foreach ($zones as $zi => $zone)
          <div class="zone-accordion-item" id="zone-item-{{ $zi }}">

            {{-- Zone header (click to toggle) --}}
            <button
              class="zone-accordion-header"
              onclick="toggleZone({{ $zi }})"
              aria-expanded="false"
              aria-controls="zone-body-{{ $zi }}"
            >
              <div class="zone-accordion-left">
                <span class="zone-accordion-name">{{ strtoupper($zone['name']) }}</span>
                <span class="zone-accordion-detail">see details
                  <svg class="zone-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none"
                       stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                       aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"/>
                  </svg>
                </span>
              </div>
              <div class="zone-accordion-stats">
                <div class="zone-accordion-stat">
                  <span class="zone-accordion-stat-label">Total<br>Members</span>
                  <span class="zone-accordion-stat-value">{{ $zone['members'] }}</span>
                </div>
                <div class="zone-accordion-stat">
                  <span class="zone-accordion-stat-label">Total<br>Masjids</span>
                  <span class="zone-accordion-stat-value">{{ $zone['masjids'] }}</span>
                </div>
              </div>
            </button>

            {{-- Zone body (masjid list, hidden by default) --}}
            <div class="zone-accordion-body" id="zone-body-{{ $zi }}" hidden>
              @foreach ($zone['centers'] as $ci => $center)
                <div class="masjid-item" id="masjid-item-{{ $zi }}-{{ $ci }}">

                  {{-- Masjid row --}}
                  <button
                    class="masjid-row"
                    onclick="toggleMasjid({{ $zi }}, {{ $ci }})"
                    aria-expanded="false"
                    aria-controls="masjid-zips-{{ $zi }}-{{ $ci }}"
                  >
                    <div style="display:flex;align-items:center;gap:var(--sp-3);">
                      <img
                        src="{{ asset('images/' . $center['img']) }}"
                        alt="{{ $center['name'] }}"
                        class="masjid-img"
                      />
                      <span class="masjid-name">{{ $center['name'] }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:var(--sp-3);">
                      <span class="masjid-total-badge">Total Members {{ $center['total'] }}</span>
                      <svg class="masjid-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"
                           stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                           aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"/>
                      </svg>
                    </div>
                  </button>

                  {{-- ZIP rows (hidden by default) --}}
                  @if (!empty($center['zips']))
                    <div class="masjid-zips" id="masjid-zips-{{ $zi }}-{{ $ci }}" hidden>
                      <div class="masjid-zips-header">
                        <span>ZIP Code</span>
                        <span>Total Members</span>
                      </div>
                      @foreach ($center['zips'] as $zip)
                        <div class="masjid-zip-row">
                          <span>{{ $zip['code'] }}</span>
                          <span>{{ $zip['count'] }}</span>
                        </div>
                      @endforeach
                    </div>
                  @endif

                </div>
              @endforeach
            </div>

          </div>
        @endforeach

      </div>{{-- /zone-accordion --}}
    </div>{{-- /zone wrapper --}}

  </div>{{-- /bottom grid --}}

@endsection

@push('scripts')
<script>
/* ── Sparkline bar charts for stat cards ──────────────────── */
function makeSparkline(id, data, color) {
  var canvas = document.getElementById(id);
  if (!canvas || typeof Chart === 'undefined') return;
  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: data.map(function(_,i){ return i; }),
      datasets: [{
        data: data,
        backgroundColor: data.map(function(_,i){
          return i === data.length - 1 ? color.solid : color.faded;
        }),
        borderRadius: 3,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: {
        x: { display: false },
        y: { display: false }
      },
      animation: false,
    }
  });
}

makeSparkline('spark-total',  [3,5,4,6,5,7,6,8,7,9], { solid:'#f59e0b', faded:'rgba(245,158,11,.35)' });
makeSparkline('spark-active', [2,4,3,5,4,6,5,7,6,8], { solid:'#3aab7b', faded:'rgba(58,171,123,.35)' });
makeSparkline('spark-lapsed', [1,2,1,3,2,3,2,4,3,5], { solid:'#c4b5a0', faded:'rgba(196,181,160,.35)' });

/* ── Membership pie chart ─────────────────────────────────── */
ISGH.initPieChart(
  'membership-pie',
  ['Individual', 'Checkomatic', 'Lifetime'],
  [{{ $levelBreakdown['individual'] ?? 31743 }},
   {{ $levelBreakdown['checkmatic'] ?? 54438 }},
   {{ $levelBreakdown['lifetime']   ?? 52434 }}],
  ['#1a5c42', '#f59e0b', '#3aab7b']
);

/* ── Profile donut ────────────────────────────────────────── */
ISGH.initPieChart(
  'profile-pie',
  ['Active', 'Lapsed'],
  [{{ $profileStatus['active'] ?? 98539 }},
   {{ $profileStatus['lapsed'] ?? 796 }}],
  ['#3aab7b', '#f59e0b']
);

/* ── Zone accordion ───────────────────────────────────────── */
function toggleZone(zi) {
  var body    = document.getElementById('zone-body-' + zi);
  var btn     = body.previousElementSibling;
  var chevron = btn.querySelector('.zone-chevron');
  var isOpen  = body.classList.contains('is-open');

  /* close every other open zone first */
  document.querySelectorAll('.zone-accordion-body.is-open').forEach(function(b) {
    if (b !== body) {
      b.classList.remove('is-open');
      b.previousElementSibling.setAttribute('aria-expanded', 'false');
      var c = b.previousElementSibling.querySelector('.zone-chevron');
      if (c) c.style.transform = '';
    }
  });

  /* toggle current zone */
  body.classList.toggle('is-open', !isOpen);
  btn.setAttribute('aria-expanded', String(!isOpen));
  chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

function toggleMasjid(zi, ci) {
  var zips    = document.getElementById('masjid-zips-' + zi + '-' + ci);
  if (!zips) return;
  var btn     = zips.previousElementSibling;
  var chevron = btn.querySelector('.masjid-chevron');
  var isOpen  = !zips.hidden;

  zips.hidden = isOpen;
  btn.setAttribute('aria-expanded', String(!isOpen));
  chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}
</script>
@endpush

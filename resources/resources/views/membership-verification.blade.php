<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}"/>
  <title>Membership Verification - ISGH</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    @font-face {
      font-family: "SF Pro regular";
      src: url("{{ asset('fonts/SFPRODISPLAYREGULAR.OTF') }}") format("woff2");
      font-weight: 400;
      font-style: normal;
    }
    @font-face {
      font-family: "SF Pro bold";
      src: url("{{ asset('fonts/SFPRODISPLAYBOLD.OTF') }}") format("woff2");
      font-weight: 700;
      font-style: normal;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green:       #0d7a55;
      --green-dark:  #085c40;
      --green-mid:   #10b981;
      --green-light: #e6f4ee;
      --bg:          #f8f8f8;
    }

    html, body {
      height: 100%;
      font-family: 'SF Pro regular', 'DM Sans', sans-serif;
      background: var(--bg);
    }

    /* ─── NAVBAR ──────────────────────────────────── */
        .nav{
        border: 10px solid #ffff;
      }
    .navbar-glass {
      background: rgba(10,10,10,0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
    }

    /* ─── HERO ───────────────────────────────────── */
    .hero-bg {
      background: linear-gradient(135deg, #31744aff 0%, #83968fff 100%);
      position: relative;
      overflow: hidden;
    }
    .hero-bg::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url('{{ asset("images/bussinesshandshake.png") }}');
      background-size: cover;
      background-position: center;
      mix-blend-mode: overlay;
      opacity: 0.9;
      z-index: 1;
    }

    /* ─── MAIN CONTAINER ──────────────────────────── */
    .main-container {
      max-width: 680px;
      margin: -2rem auto 0;
      padding: 0 1.5rem 5rem;
      position: relative;
      z-index: 20;
    }

    /* ─── VERIFICATION CARD ───────────────────────── */
    .verify-card {
      background: white;
      border-radius: 1.75rem;
      padding: 2.5rem 2.5rem 3rem;
      box-shadow: 0 4px 32px rgba(0,0,0,0.07), 0 1px 8px rgba(0,0,0,0.04);
      border: 1px solid #f1f5f9;
      margin-bottom: 1.5rem;
    }

    .card-title {
      font-family: 'SF Pro bold';
      font-size: 1.65rem;
      color: #111827;
      text-align: center;
      margin-bottom: 0.6rem;
    }

    .card-subtitle {
      font-size: 0.83rem;
      color: #6b7280;
      text-align: center;
      line-height: 1.6;
      margin-bottom: 0.35rem;
    }

    .card-note {
      font-size: 0.78rem;
      color: #9ca3af;
      text-align: center;
      font-style: italic;
      margin-bottom: 1.75rem;
    }

    .divider {
      height: 1px;
      background: #f1f3f5;
      margin: 0 0 1.75rem;
    }

    /* ─── FORM FIELDS ─────────────────────────────── */
    .fields-stack {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
      margin-bottom: 1.75rem;
    }

    .field {
      position: relative;
      text-align: left;
    }

    .field label {
      position: absolute;
      top: -0.52rem;
      left: 0.9rem;
      background: #fff;
      padding: 0 0.3rem;
      font-size: 0.7rem;
      font-weight: 700;
      color: #374151;
      z-index: 5;
      font-family: 'SF Pro bold';
      letter-spacing: 0.01em;
      line-height: 1;
    }

    .field label span { color: #ef4444; }

    .field input {
      width: 100%;
      border: 1.5px solid #e2e8f0;
      border-radius: 0.75rem;
      padding: 0.9rem 1rem;
      font-size: 0.86rem;
      font-family: 'SF Pro regular', sans-serif;
      color: #111827;
      background: white;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .field input:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16,185,129,0.09);
    }

    .field input::placeholder { color: #c4cad4; font-size: 0.83rem; }

    /* ─── VERIFY BUTTON ───────────────────────────── */
    .btn-verify {
      width: 100%;
      padding: 1rem;
      background: #043d27;
      color: white;
      border: none;
      border-radius: 999px;
      font-size: 0.95rem;
      font-family: 'SF Pro bold';
      cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
      letter-spacing: 0.01em;
    }

    .btn-verify:hover:not(:disabled) {
      background: #033020;
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(4,61,39,0.22);
    }
    .btn-verify:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }

    .status-badge-lapsed   { background: #fee2e2; color: #991b1b; }
    .status-badge-pending  { background: #fff7ed; color: #9a3412; }
    .status-badge-active   { background: #dcfce7; color: #15803d; }
    .status-badge-default  { background: #f3f4f6; color: #374151; }

    /* ─── RESULT CARD ─────────────────────────────── */
    .result-card {
      background: white;
      border-radius: 1.75rem;
      padding: 1.75rem 2rem 2rem;
      box-shadow: 0 4px 32px rgba(0,0,0,0.07), 0 1px 8px rgba(0,0,0,0.04);
      border: 1px solid #f1f5f9;
      position: relative;
      overflow: hidden;
    }

    /* Subtle mosque silhouette watermark */
    .result-card::before {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      width: 280px;
      height: 200px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3Cg fill='%230d7a55' opacity='0.06'%3E%3Crect x='20' y='120' width='30' height='80'/%3E%3Cellipse cx='35' cy='120' rx='15' ry='25'/%3E%3Crect x='10' y='140' width='50' height='60'/%3E%3Crect x='130' y='80' width='40' height='120'/%3E%3Cellipse cx='150' cy='80' rx='20' ry='35'/%3E%3Crect x='115' y='110' width='70' height='90'/%3E%3Crect x='245' y='120' width='30' height='80'/%3E%3Cellipse cx='260' cy='120' rx='15' ry='25'/%3E%3Crect x='235' y='140' width='50' height='60'/%3E%3C/g%3E%3C/svg%3E");
      background-size: cover;
      background-position: bottom right;
      pointer-events: none;
      z-index: 0;
    }

    .result-inner { position: relative; z-index: 1; }

    .result-header {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      margin-bottom: 0.35rem;
    }

    .result-checkbox {
      width: 28px;
      height: 28px;
      background: #10b981;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .result-checkbox svg { width: 16px; height: 16px; color: white; }

    .result-title {
      font-family: 'SF Pro bold';
      font-size: 1.05rem;
      color: #111827;
    }

    .result-sub {
      font-size: 0.78rem;
      color: #9ca3af;
      margin-bottom: 1.5rem;
      padding-left: 2.7rem;
    }

    /* ─── RESULT GRID ─────────────────────────────── */
    .result-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.4rem 2rem;
    }

    .result-field {}

    .result-label {
      font-size: 0.72rem;
      color: #9ca3af;
      margin-bottom: 0.25rem;
      font-family: 'SF Pro regular';
    }

    .result-value {
      font-family: 'SF Pro bold';
      font-size: 0.95rem;
      color: #111827;
      min-height: 1.25rem;
      transition: color 0.2s;
    }

    .result-value.empty { color: #d1d5db; font-family: 'SF Pro regular'; font-style: italic; }

    /* Status badge */
    .status-badge {
      display: inline-block;
      padding: 0.2rem 0.65rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-family: 'SF Pro bold';
      background: #dcfce7;
      color: #15803d;
    }

    /* ─── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 560px) {
      .verify-card { padding: 1.75rem 1.25rem 2rem; }
      .result-card  { padding: 1.5rem 1.25rem 1.75rem; }
      .result-grid  { grid-template-columns: 1fr; gap: 1.1rem; }
    }
  </style>
</head>

<body>
<div style="border:10px solid #fff; border-radius:40px; background:rgba(248,248,248,1);">

  <!-- ══════════ HEADER ══════════ -->
  <header class="w-full pt-8 relative z-50 flex items-center justify-between px-4 sm:px-8 md:px-16 lg:px-24">
    <div class="flex-shrink-0">
      <div class="w-14 h-14 rounded-full border border-[#c8a84b] flex items-center justify-center bg-[#1a4a2e] shadow-lg">
        <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-10 h-10 object-contain">
      </div>
    </div>
    <nav class="navbar-glass rounded-full pl-8 pr-2 py-2 flex items-center gap-8 ml-auto">
      <div class="hidden lg:flex items-center gap-7">
        <a href="{{ route('home') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Home</a>
        <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Centers</a>
        <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Donate</a>
        <a href="{{ route('join') }}" class="text-white/75 text-[15px] font-medium hover:text-gray-300 transition-colors">Become a Member</a>
        <a href="{{ route('membership-verification') }}" class="text-white/75 text-[15px] font-medium hover:text-gray-300 transition-colors">Verify Membership Status</a>
      </div>
      <div class="flex items-center gap-3">
        <a href="#" class="bg-white/20 hover:bg-white/30 text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors inline-block text-center">Sign in</a>
        <a href="{{ route('join') }}" style="background:#00d084;" class="hover:bg-[#00b870] text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors shadow-md inline-block text-center">Join Now</a>
      </div>
      <button onclick="openMobileMenu()" class="lg:hidden ml-2 pr-2 text-white/70 hover:text-white transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </nav>
  </header>

  <!-- ══════════ HERO ══════════ -->
  <section class="hero-bg min-h-[260px] sm:min-h-[420px] flex items-center justify-center py-20 px-4"
           style="border-bottom-left-radius:50px;border-bottom-right-radius:50px;position:relative;top:-86px;">
    <div class="relative z-10 flex flex-col items-center text-center max-w-3xl mx-auto gap-6 mt-16">
      <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-white drop-shadow-md tracking-tight">
        ISGH Membership Verification
      </h1>
      <p class="text-white/90 text-sm sm:text-base leading-relaxed max-w-lg drop-shadow-sm">
        Your membership supports our Masajid, provides free healthcare at Shifa Clinics, and empowers our
        youth through education. Choose the category that best fits your family and join our legacy of service.
      </p>
      <div class="inline-flex items-center gap-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-full py-2 px-6 mt-2 shadow-lg">
        <div class="flex items-center gap-1 text-yellow-400 text-lg">★ ★ ★ ★ ★</div>
        <span class="text-white text-sm font-medium" style="font-family:'SF Pro regular';">
          Join 50,000+ active members across Greater Houston.
        </span>
      </div>
    </div>
  </section>

  <!-- ══════════ MAIN CONTAINER ══════════ -->
  <div class="main-container">

    <!-- ── VERIFICATION FORM CARD ── -->
    <div class="verify-card">
      <h2 class="card-title">ISGH Membership Verification</h2>
      <p class="card-subtitle">
        Thank you for taking an interest in verifying your ISGH membership!<br>
        Please enter the info below to look up your membership account details.
      </p>
      <p class="card-note">If you feel that anything is missing or inaccurate, please contact us.</p>

      <div class="divider"></div>

      <div class="fields-stack">

        <div class="field">
          <label>First Name <span>*</span></label>
          <input type="text" id="inp-first-name" placeholder="Ali" autocomplete="given-name">
        </div>

        <div class="field">
          <label>Last Name <span>*</span></label>
          <input type="text" id="inp-last-name" placeholder="Ahmad" autocomplete="family-name">
        </div>

        <div class="field">
          <label>Phone Number <span>*</span></label>
          <input type="tel" id="inp-phone" placeholder="(713) 555-1234" autocomplete="tel">
        </div>

        <div class="field">
          <label>Email <span>*</span></label>
          <input type="email" id="inp-email" placeholder="ali44@gmail.com" autocomplete="email">
        </div>

      </div>

      <button class="btn-verify" onclick="handleVerify()">Verify Membership</button>
    </div>
    <!-- /verify-card -->


    <!-- ── NOT FOUND CARD ── -->
    <div class="result-card" id="card-not-found" style="display:none; border-color:#fecaca;">
      <div class="result-inner">
        <div class="result-header">
          <div class="result-checkbox" style="background:#ef4444;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                 stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </div>
          <span class="result-title" style="color:#991b1b;">Membership not found</span>
        </div>
        <p class="result-sub" id="not-found-msg" style="color:#ef4444;padding-left:2.7rem;">
          No record matched your details.
        </p>
        <p style="font-size:0.8rem;color:#9ca3af;padding-left:2.7rem;">
          Please double-check your information or contact
          <strong style="color:#374151;">ISGH support</strong> for assistance.
        </p>
      </div>
    </div>

    <!-- ── MEMBERSHIP FOUND CARD ── -->
    <div class="result-card" id="card-found" style="display:none;">
      <div class="result-inner">

        <div class="result-header">
          <div class="result-checkbox">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                 stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <span class="result-title">Membership found!</span>
        </div>
        <p class="result-sub">Your membership details are shown below.</p>

        <div class="result-grid">

          <div class="result-field">
            <p class="result-label">Name</p>
            <p class="result-value" id="res-name">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Email</p>
            <p class="result-value" id="res-email" style="font-size:0.83rem;word-break:break-all;">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Phone</p>
            <p class="result-value" id="res-phone">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Membership Type</p>
            <p class="result-value" id="res-type">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Status</p>
            <p class="result-value" id="res-status">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Member Since</p>
            <p class="result-value" id="res-since">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Expiry / Renewal Date</p>
            <p class="result-value" id="res-expiry">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Assigned Center</p>
            <p class="result-value" id="res-zone">—</p>
          </div>

          <div class="result-field">
            <p class="result-label">Voting Eligible</p>
            <p class="result-value" id="res-voting">—</p>
          </div>

        </div>
      </div>
    </div>
    <!-- /result-card -->

  </div>
  <!-- /main-container -->


  <!-- ══════════ FOOTER ══════════ -->
  <footer style="background:linear-gradient(135deg,#0a5e3a 0%,#0d7a4e 50%,#12a060 100%);border-radius:2rem 2rem 0 0;margin-top:5rem;border-top:10px solid white;border-left:2px solid white;border-right:2px solid white;box-shadow:0 -4px 24px rgba(180,220,255,0.25),inset 0 1px 0 rgba(255,255,255,0.15);">
    <div class="max-w-6xl mx-auto px-8 sm:px-12 py-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full border-2 border-yellow-500 flex items-center justify-center bg-green-900 flex-shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo">
          </div>
        </div>
        <p class="text-green-200 text-sm leading-relaxed" style="font-family:'SF Pro regular';">
          Islamic Society of Greater Houston – Building community through faith, education, and service.
        </p>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Quick Links</h4>
        <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> About Us</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Prayer Times</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Events</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Programs</li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Services</h4>
        <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Shifa Clinics</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Quran Academy</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Chaplaincy</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Matrimonial</li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Contact</h4>
        <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Contact Us</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Donate</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Volunteer</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Newsletter</li>
        </ul>
      </div>
    </div>
    <div class="border-t border-green-700/50 mx-8 sm:mx-12">
      <div class="max-w-6xl mx-auto py-5 flex flex-col sm:flex-row items-center justify-between gap-2" style="font-family:'SF Pro regular';">
        <p class="text-green-300 text-xs">© 2026 Islamic Society of Greater Houston. All rights reserved.</p>
        <p class="text-green-400 text-xs">Built with ❤️ for the Houston Muslim Community</p>
      </div>
    </div>
  </footer>

</div>

<!-- ══════════ VERIFICATION SCRIPT ══════════ -->
<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').content;

  function showCard(id) {
    ['card-found', 'card-not-found'].forEach(c => {
      document.getElementById(c).style.display = 'none';
    });
    if (id) {
      const el = document.getElementById(id);
      el.style.display = 'block';
      el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function setVal(id, text) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = text || '—';
    el.classList.toggle('empty', !text);
  }

  function statusBadge(status) {
    const s = (status || '').toLowerCase();
    let cls = 'status-badge status-badge-default';
    if (s === 'active')                           cls = 'status-badge status-badge-active';
    else if (s === 'lapsed' || s === 'expired')   cls = 'status-badge status-badge-lapsed';
    else if (s.startsWith('pending'))             cls = 'status-badge status-badge-pending';
    return `<span class="${cls}">${status}</span>`;
  }

  async function handleVerify() {
    const firstName = document.getElementById('inp-first-name').value.trim();
    const lastName  = document.getElementById('inp-last-name').value.trim();
    const phone     = document.getElementById('inp-phone').value.trim();
    const email     = document.getElementById('inp-email').value.trim();

    if (!email && (!firstName || !lastName)) {
      document.getElementById('not-found-msg').textContent =
        'Please enter your email address or both first and last name.';
      showCard('card-not-found');
      return;
    }

    const btn = document.querySelector('.btn-verify');
    btn.disabled = true;
    btn.textContent = 'Verifying…';
    showCard(null);

    try {
      const res = await fetch('{{ route("membership.verify") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ first_name: firstName, last_name: lastName, phone, email }),
      });

      const data = await res.json();

      if (data.success) {
        const m = data.member;
        setVal('res-name',   m.name);
        setVal('res-email',  m.email);
        setVal('res-phone',  m.phone);
        setVal('res-type',   m.type);
        setVal('res-since',  m.since);
        setVal('res-expiry', m.expiry);
        setVal('res-zone',   m.zone || '—');
        setVal('res-voting', m.voting);

        // Status with colour badge
        document.getElementById('res-status').innerHTML = statusBadge(m.status);

        showCard('card-found');
      } else {
        document.getElementById('not-found-msg').textContent =
          data.message || 'No membership record found.';
        showCard('card-not-found');
      }
    } catch (err) {
      document.getElementById('not-found-msg').textContent =
        'A network error occurred. Please try again.';
      showCard('card-not-found');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Verify Membership';
    }
  }

  // Allow Enter key to submit from any input
  document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && ['inp-first-name','inp-last-name','inp-phone','inp-email'].includes(e.target.id)) {
      handleVerify();
    }
  });
</script>

<div id="mobileMenu" class="fixed inset-0 z-[200] hidden">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeMobileMenu()"></div>
  <div class="absolute top-0 right-0 w-72 h-full bg-[#0d1f14] flex flex-col p-6 shadow-2xl overflow-y-auto">
    <button onclick="closeMobileMenu()" class="self-end text-white/70 hover:text-white mb-6">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <nav class="flex flex-col gap-1">
      <a href="{{ route('home') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Home</a>
      <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Centers</a>
      <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Donate</a>
      <a href="{{ route('join') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Become a Member</a>
      <a href="{{ route('membership-verification') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Verify Membership</a>
    </nav>
    <div class="mt-6 flex flex-col gap-3">
      <a href="https://isgh.wildapricot.org/Sys/Login" target="_blank" rel="noopener noreferrer" class="bg-white/20 text-white text-center px-6 py-2.5 rounded-full font-semibold">Sign in</a>
      <a href="{{ route('join') }}" style="background:#00d084;" class="text-white text-center px-6 py-2.5 rounded-full font-semibold">Join Now</a>
    </div>
  </div>
</div>
<script>
function openMobileMenu(){document.getElementById('mobileMenu').classList.remove('hidden');document.body.style.overflow='hidden';}
function closeMobileMenu(){document.getElementById('mobileMenu').classList.add('hidden');document.body.style.overflow='';}
</script>

</body>
</html>

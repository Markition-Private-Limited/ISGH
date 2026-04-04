<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Welcome to ISGH - Membership Confirmed</title>
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
    .navbar-glass {
      background: rgba(10,10,10,0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
    }
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
    .success-container {
      max-width: 640px;
      margin: -2rem auto 0;
      padding: 0 1.5rem 5rem;
      position: relative;
      z-index: 20;
    }
    .success-card {
      background: white;
      border-radius: 1.75rem;
      padding: 3rem 2.5rem;
      box-shadow: 0 4px 32px rgba(0,0,0,0.07), 0 1px 8px rgba(0,0,0,0.04);
      border: 1px solid #f1f5f9;
      text-align: center;
    }
    .check-circle {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #10b981, #0d7a55);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: 0 8px 32px rgba(16,185,129,0.3);
    }
    .check-circle svg {
      width: 40px;
      height: 40px;
      color: white;
    }
    .success-title {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 0.5rem;
    }
    .success-sub {
      font-size: 0.9rem;
      color: #6b7280;
      margin-bottom: 2rem;
      line-height: 1.6;
    }
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem 2rem;
      background: #f9fafb;
      border-radius: 1rem;
      padding: 1.5rem;
      margin-bottom: 2rem;
      text-align: left;
    }
    .info-label {
      font-size: 0.72rem;
      color: #9ca3af;
      margin-bottom: 0.2rem;
      font-family: 'SF Pro regular';
    }
    .info-value {
      font-family: 'SF Pro bold';
      font-size: 0.92rem;
      color: #111827;
    }
    .status-badge {
      display: inline-block;
      padding: 0.2rem 0.65rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-family: 'SF Pro bold';
      background: #dcfce7;
      color: #15803d;
    }
    .btn-home {
      display: inline-block;
      background: var(--green-dark);
      color: #fff;
      padding: 0.9rem 2.5rem;
      border-radius: 999px;
      font-size: 0.93rem;
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      text-decoration: none;
      transition: background 0.2s, transform 0.15s;
      margin-top: 0.5rem;
    }
    .btn-home:hover {
      background: var(--green);
      transform: translateY(-1px);
    }
    @media (max-width: 480px) {
      .info-grid { grid-template-columns: 1fr; }
      .success-card { padding: 2rem 1.25rem; }
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
  <section class="hero-bg min-h-[200px] flex items-center justify-center py-16 px-4"
           style="border-bottom-left-radius:50px;border-bottom-right-radius:50px;position:relative;top:-86px;">
    <div class="relative z-10 flex flex-col items-center text-center max-w-2xl mx-auto gap-4 mt-16">
      <h1 class="text-4xl sm:text-5xl font-bold text-white drop-shadow-md tracking-tight">
        Welcome to ISGH!
      </h1>
      <p class="text-white/90 text-sm sm:text-base leading-relaxed max-w-lg drop-shadow-sm">
        Your membership is confirmed. JazakAllahu Khayran for joining our community.
      </p>
    </div>
  </section>

  <!-- ══════════ MAIN ══════════ -->
  <div class="success-container">
    <div class="success-card">

      <div class="check-circle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
             stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>

      <h2 class="success-title">Membership Confirmed!</h2>
      <p class="success-sub">
        Your ISGH membership has been successfully processed.<br>
        A confirmation will be sent to <strong>{{ $member_email }}</strong>.
      </p>

      <div class="info-grid grid grid-cols-1 sm:grid-cols-2">
        <div>
          <p class="info-label">Member Name</p>
          <p class="info-value">{{ $member_name }}</p>
        </div>
        <div>
          <p class="info-label">Email</p>
          <p class="info-value">{{ $member_email }}</p>
        </div>
        <div>
          <p class="info-label">Membership Type</p>
          <p class="info-value">{{ ucwords(str_replace('_', ' ', $membership_type)) }}</p>
        </div>
        <div>
          <p class="info-label">Amount Paid</p>
          <p class="info-value">{{ $amount_label }}</p>
        </div>
        <div>
          <p class="info-label">Status</p>
          <p class="info-value"><span class="status-badge">Active</span></p>
        </div>
        @if($wa_contact_id)
        <div>
          <p class="info-label">Member ID</p>
          <p class="info-value">#{{ $wa_contact_id }}</p>
        </div>
        @endif
      </div>

      <a href="{{ route('home') }}" class="btn-home">Return to Home</a>
    </div>
  </div>

  <!-- ══════════ FOOTER ══════════ -->
  <footer style="background:linear-gradient(135deg,#0a5e3a 0%,#0d7a4e 50%,#12a060 100%);border-radius:2rem 2rem 0 0;margin-top:5rem;border-top:10px solid white;border-left:2px solid white;border-right:2px solid white;">
    <div class="max-w-6xl mx-auto px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-2" style="font-family:'SF Pro regular';">
      <p class="text-green-300 text-xs">© 2026 Islamic Society of Greater Houston. All rights reserved.</p>
      <p class="text-green-400 text-xs">Built with ❤️ for the Houston Muslim Community</p>
    </div>
  </footer>

</div>

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
      <a href="#" class="bg-white/20 text-white text-center px-6 py-2.5 rounded-full font-semibold">Sign in</a>
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

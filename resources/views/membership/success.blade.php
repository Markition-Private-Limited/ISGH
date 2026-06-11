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
    .dependent-section {
      margin-top: 1.5rem;
      text-align: left;
    }
    .dependent-section-title {
      font-family: 'SF Pro bold';
      font-size: 0.95rem;
      color: #111827;
      margin-bottom: 0.9rem;
    }
    .dependent-table-wrap {
      overflow-x: auto;
      border: 1px solid #e5e7eb;
      border-radius: 0.9rem;
      background: #fff;
    }
    .dependent-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 620px;
    }
    .dependent-table th,
    .dependent-table td {
      padding: 0.8rem 0.9rem;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
      vertical-align: top;
    }
    .dependent-table th {
      background: #f9fafb;
      font-size: 0.72rem;
      color: #6b7280;
      font-family: 'SF Pro bold';
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .dependent-table td {
      font-size: 0.88rem;
      color: #111827;
    }
    .dependent-list {
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
    }
    .dependent-item {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 0.9rem;
      padding: 1rem 1.05rem;
    }
    .dependent-item-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 0.6rem;
    }
    .dependent-item-name {
      font-family: 'SF Pro bold';
      font-size: 0.95rem;
      color: #111827;
    }
    .dependent-item-type {
      display: inline-block;
      padding: 0.18rem 0.6rem;
      border-radius: 999px;
      background: #e6f4ee;
      color: #0d7a55;
      font-size: 0.74rem;
      font-family: 'SF Pro bold';
      white-space: nowrap;
    }
    .dependent-item-meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.55rem 1rem;
    }
    .dependent-meta-label {
      font-size: 0.7rem;
      color: #9ca3af;
      margin-bottom: 0.15rem;
    }
    .dependent-meta-value {
      font-family: 'SF Pro bold';
      font-size: 0.88rem;
      color: #111827;
      line-height: 1.4;
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
      @php
        $spouseList = $spouses ?? [];
        $flatMemberList = $flat_members ?? [];
        $hasFlatMembers = $has_flat_members ?? !empty($flatMemberList);
        $hasSpouseOnly = $has_spouse_only ?? (!$hasFlatMembers && !empty($spouseList));
        $dependents = [];
        foreach ($spouseList as $spouse) {
          if (!empty($spouse['first_name']) || !empty($spouse['last_name'])) {
            $dependents[] = array_merge($spouse, ['_kind' => 'Spouse']);
          }
        }
        foreach ($flatMemberList as $member) {
          if (!empty($member['first_name']) || !empty($member['last_name'])) {
            $dependents[] = array_merge($member, ['_kind' => $member['relation'] ?? 'Family Member']);
          }
        }
        $dependentCount = count($dependents);
      @endphp

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

      @if($dependentCount > 1)
        <div class="dependent-section">
          <h3 class="dependent-section-title">Spouse and Family Members</h3>
          <div class="dependent-table-wrap">
            <table class="dependent-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Type</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Date of Birth</th>
                </tr>
              </thead>
              <tbody>
                @foreach($dependents as $dependent)
                  <tr>
                    <td>{{ trim(($dependent['first_name'] ?? '').' '.($dependent['last_name'] ?? '')) ?: '—' }}</td>
                    <td>{{ $dependent['_kind'] ?? 'Family Member' }}</td>
                    <td>{{ $dependent['email'] ?? '—' }}</td>
                    <td>{{ $dependent['phone'] ?? '—' }}</td>
                    <td>{{ $dependent['dob'] ?? '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @elseif($dependentCount === 1)
        @php $dependent = $dependents[0]; @endphp
        <div class="info-grid" style="margin-top:0;">
          <div>
            <p class="info-label">{{ $dependent['_kind'] ?? 'Dependent' }} Name</p>
            <p class="info-value">{{ trim(($dependent['first_name'] ?? '').' '.($dependent['last_name'] ?? '')) ?: '—' }}</p>
          </div>
          <div>
            <p class="info-label">{{ $dependent['_kind'] ?? 'Dependent' }} Email</p>
            <p class="info-value">{{ $dependent['email'] ?? '—' }}</p>
          </div>
          <div>
            <p class="info-label">{{ $dependent['_kind'] ?? 'Dependent' }} Phone</p>
            <p class="info-value">{{ $dependent['phone'] ?? '—' }}</p>
          </div>
          <div>
            <p class="info-label">{{ $dependent['_kind'] ?? 'Dependent' }} Date of Birth</p>
            <p class="info-value">{{ $dependent['dob'] ?? '—' }}</p>
          </div>
        </div>
      @endif

      <a href="{{ route('home') }}" class="btn-home">Return to Home</a>
    </div>
  </div>

  <!-- ══════════ FOOTER ══════════ -->
  <footer style="background:linear-gradient(135deg,#0a5e3a 0%,#0d7a4e 50%,#12a060 100%);border-radius:2rem 2rem 0 0;margin-top:5rem;border-top:10px solid white;border-left:2px solid white;border-right:2px solid white;">
    <div class="max-w-6xl mx-auto px-8 sm:px-12 py-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full border-2 border-yellow-500 flex items-center justify-center bg-green-900 flex-shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo">
          </div>
        </div>
        <p class="text-green-200 text-sm leading-relaxed" style="font-family:'SF Pro regular';">
          The Islamic Society of Greater Houston (ISGH), established in 1969, is one of the largest Islamic organizations in North America. With 22 Islamic Centers across Houston, ISGH serves the Muslim community and works alongside other faith-based organizations to support and serve the city. ISGH is a registered non-profit 501(c)(3) organization in Texas.
        </p>
        <div class="flex items-center gap-3 mt-1">
          <a href="https://www.facebook.com/share/1PMpeZKeZZ/" target="_blank" rel="noopener" aria-label="Facebook" class="w-9 h-9 rounded-full border border-white/60 flex items-center justify-center text-white hover:bg-white/15 transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987H7.898v-2.89h2.54V9.797c0-2.507 1.493-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
          </a>
          <a href="https://www.instagram.com/isgh50" target="_blank" rel="noopener" aria-label="Instagram" class="w-9 h-9 rounded-full border border-white/60 flex items-center justify-center text-white hover:bg-white/15 transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
          </a>
          <a href="https://www.tiktok.com/@htx.isgh50" target="_blank" rel="noopener" aria-label="TikTok" class="w-9 h-9 rounded-full border border-white/60 flex items-center justify-center text-white hover:bg-white/15 transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
          </a>
        </div>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Quick Links</h4>
        <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
          <li><a href="{{ route('join') }}" class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors"><span class="text-green-400">•</span> Become a Member</a></li>
          <li><a href="{{ route('membership-verification') }}" class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors"><span class="text-green-400">•</span> Verify Membership Status</a></li>
          <li><a href="{{ route('member-portal.login') }}" class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors"><span class="text-green-400">•</span> Sign In</a></li>
          <li><a href="{{ route('join') }}" class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors"><span class="text-green-400">•</span> Join Now</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Contact</h4>
        <ul class="flex flex-col gap-3 text-green-200 text-sm" style="font-family:'SF Pro regular';">
          <li class="flex items-start gap-2"><span class="text-green-400">•</span><a href="tel:+17135246615" class="hover:text-white transition-colors">(713) 524-6615 ext 105</a></li>
          <li class="flex items-start gap-2"><span class="text-green-400">•</span><a href="mailto:membership.verify@isgh.org" class="hover:text-white transition-colors">membership.verify@isgh.org</a></li>
          <li class="flex items-start gap-2"><span class="text-green-400">•</span><span>3110 Eastside St<br>Houston, TX 77098</span></li>
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

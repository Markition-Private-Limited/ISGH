<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ISGH – Join Our Mission</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
    body {
       font-family: 'SF Pro regular';
  background: rgba(249, 249, 249, 1);
 }

    .page-bg {
      background: linear-gradient(145deg, #e8ece8 0%, #dde5de 40%, #e4e9e4 100%);
      min-height: 100vh;
    }
        .nav{
        border: 10px solid #ffff;
      }
    .navbar-glass {
      background: rgba(10, 10, 10, 0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
    }

    .image-card {
      background: linear-gradient(135deg, #0a5e3f 0%, #12874f 50%, #0d6b43 100%);
      position: relative;
      overflow: hidden;
    }

    .deco-circle-1 {
      position: absolute;
      width: 280px; height: 280px;
      border-radius: 50%;
      background: rgba(255,255,255,0.06);
      top: -60px; right: -60px;
    }
    .deco-circle-2 {
      position: absolute;
      width: 180px; height: 180px;
      border-radius: 50%;
      background: rgba(255,255,255,0.05);
      bottom: 60px; left: -40px;
    }
    .deco-circle-3 {
      position: absolute;
      width: 120px; height: 120px;
      border-radius: 50%;
      background: rgba(20, 180, 100, 0.25);
      top: 120px; right: 30px;
    }

    .hands-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      filter: grayscale(100%) contrast(1.1);
      mix-blend-mode: luminosity;
      opacity: 0.75;
    }

    .badge-card {
      background: linear-gradient(135deg, rgba(10, 120, 80, 0.92) 0%, rgba(15, 150, 95, 0.88) 100%);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.2);
      box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.1);
    }

    .cta-btn {
      background: linear-gradient(135deg, #0a6644 0%, #0f8a5a 100%);
      box-shadow: 0 8px 24px rgba(10, 100, 68, 0.35);
      transition: all 0.3s ease;
    }
    .cta-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 32px rgba(10, 100, 68, 0.45);
    }

    /* Navbar active link pill */
    .nav-link-active {
      background: rgba(255,255,255,0.18);
      color: #ffffff !important;
      font-weight: 600;
    }

    /* Join Now — green pill */
    .join-btn {
      background: #1a9e5c;
      border: none;
      transition: all 0.25s ease;
    }
    .join-btn:hover {
      background: #158a4e;
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(26,158,92,0.45);
    }

    .partner-logo {
      filter: grayscale(100%);
      opacity: 0.55;
      transition: all 0.3s;
    }
    .partner-logo:hover {
      filter: grayscale(0%);
      opacity: 1;
    }

    .star { color: #f59e0b; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up-1 { animation: fadeUp 0.7s ease both 0.1s; }
    .fade-up-2 { animation: fadeUp 0.7s ease both 0.25s; }
    .fade-up-3 { animation: fadeUp 0.7s ease both 0.4s; }
    .fade-up-4 { animation: fadeUp 0.7s ease both 0.55s; }
    .fade-up-5 { animation: fadeUp 0.7s ease both 0.7s; }
    .fade-up-right { animation: fadeUp 0.8s ease both 0.35s; }
    .badge-anim-1 { animation: fadeUp 0.6s ease both 0.8s; }
    .badge-anim-2 { animation: fadeUp 0.6s ease both 1.0s; }

    .benefits-carousel {
      scrollbar-width: none;
      -ms-overflow-style: none;
      scroll-behavior: smooth;
    }
    .benefits-carousel::-webkit-scrollbar {
      display: none;
    }
    .benefit-slide {
      scroll-snap-align: center;
    }
    .benefit-dot {
      width: 12px;
      height: 12px;
      border-radius: 999px;
      background: #d1d5db;
      transition: all 0.25s ease;
    }
    .benefit-dot.active {
      width: 16px;
      background: #0f8a5a;
    }

    .category-dot {
      width: 12px;
      height: 12px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.55);
      transition: all 0.25s ease;
    }
    .category-dot.active {
      width: 16px;
      background: #f3f4f6;
    }

    .manage-actions {
      display: flex;
    }

    .category-carousel-track {
      display: grid;
    }

    .category-carousel-viewport {
      overflow: hidden;
    }

    .category-slide {
      width: 100%;
    }

    .benefit-card {
      background: #ffffff;
      box-shadow: 0 4px 24px rgba(0,0,0,0.07);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .benefit-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }
    .benefit-icon-bg {
      background: linear-gradient(135deg, #e8f8f0 0%, #d0f0e0 100%);
    }

    /* ── Manage Membership Section ── */
    .manage-section {
      background: #fafafa;
      position: relative;
    }
    .manage-divider {
      width: 85%;
      margin: 0 auto;
      height: 1px;
      background: #e5e7eb;
    }
    .manage-tab-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 1;
      height: 60px;
      padding: 0 16px;
      border-radius: 999px;
      font-family: 'SF Pro regular', sans-serif;
      font-weight: 500;
      font-size: 16px;
      border: 1px solid rgba(255, 255, 255, 0.4);
      background: #f0fdf4;
      color: #4b5563;
      cursor: pointer;
      transition: all 0.25s ease;
      white-space: nowrap;
      text-decoration: none;
      box-shadow: 0 4px 14px rgba(0,0,0,0.03), inset 0 0 0 2px #ffffff;
    }
    .manage-tab-btn:hover {
      background: #e6f6ed;
      color: #0a6644;
      box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    }
    .manage-tab-btn.active {
      background: #dda23b;
      color: #ffffff;
      font-family: 'SF Pro regular', sans-serif;
      font-weight: 500;
      font-size: 20px;
      border: 2px solid #ffffff;
      box-shadow: 0 8px 24px rgba(221, 162, 59, 0.35);
    }
    .manage-tab-btn.active:hover {
      background: #cc912e;
      box-shadow: 0 10px 28px rgba(221, 162, 59, 0.45);
    }

    .categories-section {
      background: linear-gradient(160deg, #0d7a4e 0%, #0a5e38 40%, #084d2e 100%);
      position: relative;
      overflow: hidden;
    }
    .categories-section::before {
      content: '';
      position: absolute;
      width: 320px; height: 320px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.08);
      top: -80px; left: -80px;
    }
    .categories-section::after {
      content: '';
      position: absolute;
      width: 220px; height: 220px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.06);
      top: 20px; left: 20px;
    }
    .category-row {
      background: linear-gradient(135deg, #ffffff 0%, #f0faf5 100%);
      box-shadow: 0 4px 24px rgba(0,0,0,0.10);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .category-row:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 36px rgba(0,0,0,0.15);
    }
    .cat-img {
      width: 160px;
      min-width: 160px;
      height: 130px;
      object-fit: cover;
      border-radius: 1rem;
    }
    .divider-v {
      width: 1px;
      background: #d1d5db;
      align-self: stretch;
      margin: 0 1.25rem;
    }

    .faq-item {
      background: #ffffff;
      border-radius: 1rem;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      overflow: hidden;
      transition: box-shadow 0.3s ease;
    }
    .faq-item:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.10); }
    .faq-question {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.15rem 1.5rem;
      background: none;
      border: none;
      cursor: pointer;
      text-align: left;
      gap: 1rem;
    }
    .faq-question span {
      font-family: 'DM Sans', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      color: #1a1a1a;
      line-height: 1.4;
    }
    .faq-icon {
      flex-shrink: 0;
      width: 30px; height: 30px;
      border-radius: 50%;
      background: #f3f4f6;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem;
      color: #555;
      transition: transform 0.3s ease, background 0.3s ease;
      font-style: normal;
      line-height: 1;
    }
    .faq-answer {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.4s ease;
      padding: 0 1.5rem;
    }
    .faq-answer p {
      font-size: 0.9rem;
      color: #6b7280;
      line-height: 1.7;
      padding-bottom: 1.2rem;
    }
    .faq-item.open .faq-answer { max-height: 400px; }
    .faq-item.open .faq-icon {
      background: #e8f5ee;
      color: #0a6644;
    }
    .changes-th {
      border-right: 1px solid rgba(255,255,255,0.3);
    }

    /* ── Responsive fixes ── */
    @media (max-width: 1024px) {
      .badge-anim-1, .badge-anim-2 { display: none !important; }
    }
    @media (max-width: 768px) {
      .hero-grid { gap: 1.5rem; margin-top: 2.5rem; }
      .hero-copy { gap: 1.15rem; }
      .hero-copy h1 { max-width: 22rem; margin: 0 auto; }
      .hero-copy h1 span:first-child { font-size: 34px !important; line-height: 1.05; }
      .hero-copy h1 span:last-child { font-size: 30px !important; line-height: 1.08; }
      .hero-copy p { font-size: 13px !important; line-height: 1.25; max-width: 340px; }
      .hero-copy .cta-btn { font-size: 13px; padding: 0.65rem 1.15rem; border-width: 4px; }
      .hero-copy .star { font-size: 18px; }
      .hero-copy .partner-logo img { height: 32px; width: auto; }
      .hero-visual { margin-top: 0.5rem; }
      .hero-visual .image-card { height: clamp(420px, 98vw, 622px) !important; border-width: 6px !important; border-radius: 34px !important; }
      .hero-badges-mobile { display: block !important; }
      .benefits-title { font-size: 28px !important; line-height: 1.1 !important; }
      .benefits-subtitle { font-size: 13px !important; line-height: 1.35 !important; max-width: 340px; margin-left: auto; margin-right: auto; }
      .benefits-arrows { display: flex !important; }
      .benefits-track { display: flex !important; overflow-x: auto; padding-bottom: 0.5rem; margin-left: -1.25rem; margin-right: -1.25rem; padding-left: 1.25rem; padding-right: 1.25rem; scroll-snap-type: x mandatory; }
      .benefit-slide { flex: 0 0 86%; max-width: 86%; }
      .benefits-dots { display: flex !important; }
      .manage-section { padding-top: 3rem; padding-bottom: 3rem; }
      .manage-section .manage-divider { display: none; }
      .manage-section h2 { font-size: 28px !important; line-height: 1.1 !important; max-width: 14ch; margin-left: auto; margin-right: auto; }
      .manage-section p { font-size: 13px !important; line-height: 1.35 !important; max-width: 340px; margin-left: auto; margin-right: auto; }
      .manage-actions { flex-direction: column !important; gap: 0.9rem !important; align-items: center !important; width: 100% !important; margin-bottom: 1.25rem !important; }
      .manage-tab-btn { width: min(100%, 320px); min-height: 52px; height: auto; white-space: normal; padding: 14px 18px !important; }
      .manage-tab-btn.active { font-size: 17px; }
      .manage-tab-btn:not(.active) { font-size: 14px; }
      .category-section-title { font-size: 28px !important; line-height: 1.1 !important; max-width: 15ch; margin-left: auto; margin-right: auto; }
      .category-section-subtitle { font-size: 13px !important; line-height: 1.35 !important; max-width: 330px; margin-left: auto; margin-right: auto; }
      .category-carousel-shell { padding-top: 2rem; padding-bottom: 2rem; border-width: 8px !important; border-radius: 28px !important; }
      .category-carousel-controls { display: flex !important; }
      .category-carousel-dots { display: flex !important; }
      .category-carousel-viewport { margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; }
      .category-carousel-track { display: flex !important; flex-direction: row !important; flex-wrap: nowrap; gap: 0; transition: none; will-change: auto; }
      .category-slide { flex: 0 0 100%; min-width: 100%; max-width: 100%; display: flex !important; flex-direction: column !important; }
      .category-slide .p-3 { padding: 0.75rem 0.75rem 0 0.75rem; width: 100%; }
      .category-slide .p-6 { padding: 1rem 1rem 1.1rem 1rem; width: 100%; }
      .category-slide img { width: 100% !important; height: 220px !important; max-height: 220px !important; object-fit: cover; }
      .category-slide.is-hidden-mobile { display: none !important; }
      .category-row { flex-direction: column; align-items: flex-start; }
      .cat-img { width: 100%; max-width: none; height: 160px; margin-bottom: 0.75rem; }
      .divider-v { display: none; }
      .footer-desktop { display: none !important; }
      .footer-mobile { display: block !important; }
      .site-footer { border-radius: 2rem 2rem 0 0; }
      .sixia{
        display:block !important
      }
      .sixia a{
        margin-bottom:10px !important
      }
    }
  </style>
</head>
<body class="page-bg">
  <!-- Hero wrapper: remove fixed height, keep border/radius -->
  <div style="border: 10px solid #ffff; border-radius: 25px;">
    <header class="w-full pt-4 sm:pt-8 relative z-50 px-3 sm:px-8 md:px-16 lg:px-24">
      <div class="flex items-center gap-3">
        <a href="{{ route('home') }}" class="hidden lg:block shrink-0">
          <div class="w-14 h-14 rounded-full border border-[#c8a84b] flex items-center justify-center bg-[#1a4a2e] shadow-lg">
              <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-10 h-10 object-contain">
          </div>
        </a>

        <nav class="hidden lg:flex navbar-glass border border-[5px] border-white rounded-full pl-8 pr-2 py-2 items-center gap-8 ml-auto">
          <div class="flex items-center gap-7">
            <a href="{{ route('home') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Home</a>
            <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Centers</a>
            <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Donate</a>
            <a href="{{ route('join') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Become a Member</a>
            <a href="{{ route('membership-verification') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Verify Membership Status</a>
          </div>

          <div class="flex items-center gap-3">
            <a href="https://isgh.wildapricot.org/Sys/Login" target="_blank" rel="noopener noreferrer" class="bg-white/20 hover:bg-white/30 text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors inline-block text-center">Sign in</a>
            <a href="{{ route('join') }}" style="background: #00d084;" class="hover:bg-[#00b870] text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors shadow-md inline-block text-center">Join Now</a>
          </div>
        </nav>

        <div class="w-full lg:hidden">
          <div class="flex w-full items-center justify-between rounded-full border-[8px] border-white bg-[#1c1c1c] px-4 py-3 shadow-[0_12px_30px_rgba(0,0,0,0.18)] min-h-[72px]">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="ISGH Home">
              <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="h-11 w-11 sm:h-12 sm:w-12 object-contain">
            </a>

            <button onclick="openMobileMenu()" aria-label="Open menu" class="flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-white/5">
              <span class="flex flex-col items-center justify-center gap-1.5">
                <span class="block h-0.5 w-6 rounded-full bg-white"></span>
                <span class="block h-0.5 w-6 rounded-full bg-white"></span>
                <span class="block h-0.5 w-6 rounded-full bg-white"></span>
              </span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero grid: responsive padding, no fixed height/margins -->
    <div class="hero-grid px-4 sm:px-8 md:px-16 lg:px-24 grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 items-center mt-12 sm:mt-16 pb-10">
      <!-- Left text block: remove fixed dimensions -->
      <div class="hero-copy flex flex-col gap-6 order-1 lg:order-1 text-center lg:text-left">
        <div class="fade-up-1">
          <h1 class="leading-tight">
            <span class="text-emerald-700 text-4xl sm:text-5xl xl:text-6xl font-extrabold block" style="font-family: 'SF Pro regular';">Join Our Mission:</span>
            <span class="text-gray-900 text-4xl sm:text-5xl xl:text-6xl font-extrabold block" style="font-family: 'SF Pro regular';">Be the Voice of Your Community</span>
          </h1>
        </div>

        <p class="fade-up-2 text-gray-500 text-base sm:text-lg leading-relaxed mx-auto lg:mx-0 max-w-xl">
          Join one of the largest Muslim communities in North America. Support your local masjid, gain voting rights, and help shape the future of our community.
        </p>

        <div class="fade-up-3 mt-5 flex justify-center lg:justify-start">
          <a href="{{ route('join') }}" class="cta-btn border border-white border-[5px] text-white font-semibold text-base px-7 py-3.5 rounded-full flex items-center gap-3 w-fit inline-block">
            Become a Member
            <span class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
              </svg>
            </span>
          </a>
        </div>

        <div class="fade-up-4 flex items-center justify-center lg:justify-start gap-3 flex-wrap mt-5">
          <div class="flex items-center gap-0.5">
            <span class="star text-xl">★</span>
            <span class="star text-xl">★</span>
            <span class="star text-xl">★</span>
            <span class="star text-xl">★</span>
            <span class="star text-xl">★</span>
          </div>
          <p class="text-gray-600" style="font-size: 16px;font-weight: bold;">Trusted by <strong class="text-gray-800">thousands</strong> of active members across Greater Houston.</p>
        </div>

        <div class="fade-up-5 flex items-center justify-center lg:justify-start gap-6 flex-wrap pt-1 mt-5">
          <div class="partner-logo flex items-center gap-1.5">
            <img src="{{ asset('images/food.png') }}" alt="" width="95px" height="32px">
          </div>
          <div class="partner-logo flex items-center gap-1.5">
            <div class=" rounded-full bg-pink-100 flex items-center justify-center">
              <img src="{{ asset('images/shifa.png') }}" alt="" width="39px" height="39px">
            </div>
          </div>
          <div class="partner-logo flex items-center gap-1.5">
            <div class="rounded-full bg-green-100 flex items-center justify-center">
              <img src="{{ asset('images/shifausa.png') }}" alt="" width="130px" height="41px">
            </div>
          </div>
        </div>
      </div>

      <!-- Right image column -->
      <div class="hero-visual relative order-2 lg:order-2 fade-up-right">
        <!-- Image card: full width on mobile, max 561px on large screens -->
        <div class="image-card overflow-hidden relative mx-auto" style="background: url('{{ asset('images/Frame 116.png') }}') center/cover no-repeat; width: 100%; max-width: 561px; float: none; height: clamp(300px, 50vw, 622px); border: 8px solid white;
    border-radius: 40px;">
          <div class="absolute bottom-0 left-0 right-0 h-32 z-10"></div>
        </div>

        <div class="hero-badges-mobile hidden lg:hidden absolute left-0 right-0 bottom-7 z-30">
          <div class="relative h-[110px]">
            <div class="absolute left-[-6px] bottom-6 w-[185px] max-w-[48vw] rounded-[16px] border border-white/70 bg-[linear-gradient(108.46deg,rgba(31,171,118,0.75)_0%,rgba(71,191,149,0.92)_100%)] p-3 shadow-[0_12px_28px_rgba(0,0,0,0.22)] backdrop-blur-md">
              <div class="flex items-center gap-2">
                <img src="{{ asset('images/mosque.png') }}" alt="Mosque" class="h-10 w-10 flex-shrink-0 object-contain">
                <p class="text-white text-[11px] leading-snug font-semibold">
                  22+ Masajids (Centers Across greater Houston)
                </p>
              </div>
            </div>

            <div class="absolute right-[-6px] bottom-[-8px] w-[190px] max-w-[49vw] rounded-[16px] border border-white/70 bg-[linear-gradient(108.46deg,rgba(31,171,118,0.75)_0%,rgba(71,191,149,0.92)_100%)] p-3 shadow-[0_12px_28px_rgba(0,0,0,0.22)] backdrop-blur-md">
              <div class="flex items-center gap-2">
                <img src="{{ asset('images/hands1.png') }}" alt="Handshake" class="h-9 w-10 flex-shrink-0 object-contain">
                <p class="text-white text-[11px] leading-snug font-semibold">
                  Thousands of active members (Serving the Muslim Community)
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Badge cards: hidden on screens < 1024px -->
        <div class="badge-anim-1 hero-badges-desktop hidden lg:block absolute z-20 p-[3px] rounded-[18px] bg-[linear-gradient(262.95deg,#FFFFFF_0.59%,#999999_99.41%)] inline-block" style="top: 375px; left: -60px;width: 360px;height: 107px;">
          <div class="rounded-[16px] p-[14px_18px] flex items-center gap-3 bg-[linear-gradient(108.46deg,rgba(31,171,118,0.28)_0%,rgba(71,191,149,0.66)_100%)] backdrop-blur-[28px] shadow-[0_10px_27px_rgba(0,0,0,0.55)]" style="width: 355px;height: 100px;">
            <img src="{{ asset('images/mosque.png') }}" alt="Mosque" style="width:52px;height:52px;object-fit:contain;flex-shrink:0;"/>
            <p style="color:#ffffff;font-family:'DM Sans',sans-serif;font-weight:700;font-size:15px;line-height:1.4;margin:0;">22+ Masajid (Centers across<br/>Greater Houston)</p>
          </div>
        </div>

        <div class="badge-anim-2 hero-badges-desktop hidden lg:block absolute p-[3px] rounded-[18px] bg-[linear-gradient(262.95deg,#FFFFFF_0.59%,#999999_99.41%)] inline-block" style="top: 456px; left: 240px;width: 360px;height: 107px;">
          <div class="rounded-[16px] p-[14px_18px] flex items-center gap-3 bg-[linear-gradient(108.46deg,rgba(31,171,118,0.28)_0%,rgba(71,191,149,0.66)_100%)] backdrop-blur-[28px] shadow-[0_10px_27px_rgba(0,0,0,0.55)]" style="width: 355px;height: 100px;">
            <img src="{{ asset('images/hands1.png') }}" alt="Handshake" style="width:89px;height:53px;object-fit:contain;flex-shrink:0;"/>
            <p style="color:#ffffff;font-family:'DM Sans',sans-serif;font-weight:700;font-size:15px;line-height:1.4;margin:0;">Thousands Members (Serving<br/>the Muslim Community)</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== WHY JOIN SECTION ===================== -->
  <section class="w-full relative" style="background: linear-gradient(145deg, #e8ece8 0%, #dde5de 40%, #e4e9e4 100%);">
    <div class="max-w-3xl mx-auto px-5 sm:px-8 pt-16 pb-16 text-center">
      <h2 class="benefits-title text-gray-900 text-4xl sm:text-5xl font-extrabold mb-5">
        Why Become an ISGH Member? (Exclusive Member Benefits) 

      </h2>
      <p class="benefits-subtitle text-gray-500 text-base sm:text-lg leading-relaxed max-w-xl mx-auto">
        Becoming a member is more than just a registration—it's your contribution to the backbone of the Houston Muslim community.
      </p>

      <div class="flex justify-center mt-4 mb-10">
        <img src="{{ asset('images/Line 2.png') }}" alt="">
      </div>

      <div class="flex justify-center gap-3 mb-5 benefits-arrows hidden sm:hidden">
        <button type="button" id="benefits-prev" class="w-12 h-12 rounded-full bg-[#3b3b3b] text-white flex items-center justify-center shadow-md" aria-label="Previous benefit">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 18l-6-6 6-6"/>
          </svg>
        </button>
        <button type="button" id="benefits-next" class="w-12 h-12 rounded-full bg-[#3b3b3b] text-white flex items-center justify-center shadow-md" aria-label="Next benefit">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 6l6 6-6 6"/>
          </svg>
        </button>
      </div>

      <div class="benefits-track grid grid-cols-1 sm:grid-cols-2 gap-5 text-left">
        <div class="benefit-slide benefit-card rounded-3xl p-6 flex flex-col gap-0">
          <div class="benefit-icon-bg rounded-2xl flex items-center justify-center w-full mb-5" style="height:220px;">
            <img src="{{ asset('images/vote.png') }}" alt="">
          </div>
          <h3 class="text-gray-900 font-bold text-xl mb-2">Voting Rights</h3>
          <p class="text-gray-500 text-sm leading-relaxed">
            <strong class="text-gray-700">Empower Your Voice.</strong> Active members are eligible to vote in ISGH elections and contribute to shaping the leadership and policies of the community.
          </p>
        </div>

        <div class="benefit-slide benefit-card rounded-3xl p-6 flex flex-col gap-0">
          <div class="benefit-icon-bg rounded-2xl flex items-center justify-center w-full mb-5" style="height:220px;">
            <img src="{{ asset('images/tag.png') }}" alt="">
          </div>
          <h3 class="text-gray-900 font-bold text-xl mb-2">Exclusive Discounts</h3>
          <p class="text-gray-500 text-sm leading-relaxed">
            <strong class="text-gray-700">Exclusive Member Savings.</strong> Enjoy discounted rates on essential services, including funeral services (Tadfeen), weekend schools, and youth programs.
          </p>
        </div>

        <div class="benefit-slide benefit-card rounded-3xl p-6 flex flex-col gap-0">
          <div class="benefit-icon-bg rounded-2xl flex items-center justify-center w-full mb-5" style="height:220px;">
            <img src="{{ asset('images/search.png') }}" alt="">
          </div>
          <h3 class="text-gray-900 font-bold text-xl mb-2">Community Portal Access</h3>
          <p class="text-gray-500 text-sm leading-relaxed">
            <strong class="text-gray-700">Stay Informed.</strong> Get exclusive access to the ISGH Community Portal, where you can view financial reports, project updates, and important community news.
          </p>
        </div>

        <div class="benefit-slide benefit-card rounded-3xl p-6 flex flex-col gap-0">
          <div class="benefit-icon-bg rounded-2xl flex items-center justify-center w-full mb-5" style="height:220px;">
            <img src="{{ asset('images/mosque.png') }}" alt="">
          </div>
          <h3 class="text-gray-900 font-bold text-xl mb-2">Make a real Impact</h3>
          <p class="text-gray-500 text-sm leading-relaxed">
            <strong class="text-gray-700">Make a Lasting Impact.</strong> Your membership acts as Sadaqah Jariyah, sustaining masajid and Shifa clinics that provide vital services and healthcare to those in need.
          </p>
        </div>
      </div>

      <div class="benefits-dots hidden sm:hidden justify-center gap-2 mt-4 mb-6">
        <button type="button" class="benefit-dot active" aria-label="Go to benefit 1"></button>
        <button type="button" class="benefit-dot" aria-label="Go to benefit 2"></button>
        <button type="button" class="benefit-dot" aria-label="Go to benefit 3"></button>
        <button type="button" class="benefit-dot" aria-label="Go to benefit 4"></button>
      </div>

      <div class="mt-12 flex justify-center">
        <a href="{{ route('join') }}" class="cta-btn text-white font-semibold text-base px-8 py-4 rounded-full flex items-center gap-3 inline-block">
          Become a Member Today
          <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
          </span>
        </a>
      </div>
    </div>
  </section>

  <!-- ===================== MANAGE MEMBERSHIP SECTION ===================== -->
  <section class="manage-section w-full py-16 px-5 sm:px-8">
    <div class="max-w-5xl mx-auto text-center">

      <!-- Top divider -->
      <div class="manage-divider mb-14"></div>

      <!-- Heading -->
      <h2 class="text-[#1a1a1a] text-4xl sm:text-[40px] font-extrabold mb-3 tracking-tight" style="font-family: 'SF Pro bold';">
        Manage Your ISGH Membership
      </h2>
      <p class="text-gray-500 text-[17px] mb-12" style="font-family: 'SF Pro regular';">
        Take control of your community involvement in just a few clicks.
      </p>

      <!-- Action Buttons — responsive wrapping row -->
      <div class="manage-actions mb-14 sixia" style="flex-direction:row; align-items:center; justify-content:center; gap:16px; flex-wrap:wrap; width:100%;">

        <a href="{{ route('membership-verification') }}" class="manage-tab-btn">
          Verify Membership Voting Status
        </a>

        <a href="{{ route('join') }}" class="manage-tab-btn active" style="padding: 0 24px;">
          Sign-up for 2026 Membership
        </a>

        <a href="#" class="manage-tab-btn">
          Membership Portal Access ‐ Sign in
        </a>

      </div>

      <!-- Bottom divider -->
      <div class="manage-divider"></div>

    </div>
  </section>

  <!-- ===================== THREE CATEGORIES SECTION ===================== -->
<section 
  class="category-carousel-shell relative w-full py-16 px-5 sm:px-8 container m-auto border border-[10px] border-white rounded-[30px] overflow-hidden"
  style="
    background: linear-gradient(135deg, #0b3d2e 0%, #0f5a3c 40%, #138a5a 75%, #1aa06d 100%);
  "
>

  <!-- Top-left glow -->
  <div style="
    position:absolute;
    top:-120px;
    left:-120px;
    width:400px;
    height:400px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    z-index:0;
  "></div>

  <!-- Soft overlay shape -->
  <div style="
    position:absolute;
    top:0;
    left:0;
    width:500px;
    height:500px;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.05), transparent 60%);
    filter: blur(10px);
    z-index:0;
  "></div>

  <!-- ✅ CURLS (THIS was missing) -->
  <svg 
    style="
      position:absolute;
      top:-40px;
      left:-40px;
      width:380px;
      height:380px;
      z-index:0;
      opacity:0.8;
    "
    viewBox="0 0 300 300" 
    fill="none"
  >
    <!-- Curl 1 -->
    <path 
      d="M30 10 
         C160 10, 160 150, 30 150 
         C-60 150, -60 300, 120 300"
      stroke="white" 
      stroke-width="5"
      stroke-linecap="round"
    />

    <!-- Curl 2 -->
    <path 
      d="M60 40 
         C190 40, 190 180, 60 180 
         C-30 180, -30 330, 150 330"
      stroke="white" 
      stroke-width="5"
      stroke-linecap="round"
    />
  </svg>

  <!-- CONTENT -->
  <div class="relative z-10 max-w-4xl mx-auto">

    <!-- Heading -->
    <div class="text-center mb-12">
      <h2 class="category-section-title text-white text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight mb-4">
        Select the membership option that best fits your commitment to supporting the community.
        {{-- <br/>ISGH members to pick from --}}
      </h2>
      <p class="category-section-subtitle text-green-200 text-base sm:text-lg">
        Take control of your community involvement in just a few clicks.
      </p>
    </div>

    <div class="flex justify-center gap-3 mb-5 category-carousel-controls hidden sm:hidden">
      <button type="button" id="category-prev" class="w-12 h-12 rounded-full bg-[#3b3b3b] text-white flex items-center justify-center shadow-md" aria-label="Previous category">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 18l-6-6 6-6"/>
        </svg>
      </button>
      <button type="button" id="category-next" class="w-12 h-12 rounded-full bg-[#3b3b3b] text-white flex items-center justify-center shadow-md" aria-label="Next category">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 6l6 6-6 6"/>
        </svg>
      </button>
    </div>

    <!-- Category Rows -->
    <div class="category-carousel-viewport">
      <div class="category-carousel-track flex flex-col gap-5">

      <!-- Row 1 -->
      <div class="category-slide rounded-2xl flex flex-col sm:flex-row overflow-hidden shadow-md bg-white/80 backdrop-blur">
        <div class="p-3">
          <img src="{{ asset('images/couple.jpg')}}" class="w-full sm:w-40 object-cover rounded-xl" style="min-height:150px; max-height:180px;">
        </div>
        <div class="p-6 flex flex-col justify-center">
          <h3 class="text-gray-900 text-xl sm:text-2xl font-bold mb-3">Annual Membership</h3>
          <p class="text-gray-600 text-sm">$20 per year. This membership provides full access to ISGH benefits, including voting rights and community participation. Membership is valid through December 31 of each year and must be renewed annually to remain active.</p>
        </div>
      </div>

      <!-- Row 2 -->
      <div class="category-slide rounded-2xl flex flex-col sm:flex-row overflow-hidden shadow-md bg-white/80 backdrop-blur">
        <div class="p-3">
          <img src="{{ asset('images/quran1.jpg')}}" class="w-full sm:w-40 object-cover rounded-xl" style="min-height:150px; max-height:180px;">
        </div>
        <div class="p-6 flex flex-col justify-center">
          <h3 class="text-gray-900 text-xl sm:text-2xl font-bold mb-3">Checkomatic</h3>
          <p class="text-gray-600 text-sm">A convenient monthly contribution option with a minimum of $10 per month directed to your selected masjid. While membership remains active, voting eligibility requires that total contributions reach $40 by June 30 of each year.</p>
        </div>
      </div>

      <!-- Row 3 -->
      <div class="category-slide rounded-2xl flex flex-col sm:flex-row overflow-hidden shadow-md bg-white/80 backdrop-blur">
        <div class="p-3">
          <img src="{{ asset('images/hands.jpg')}}" class="w-full sm:w-40 object-cover rounded-xl" style="min-height:150px; max-height:180px;">
        </div>
        <div class="p-6 flex flex-col justify-center">
          <h3 class="text-gray-900 text-xl sm:text-2xl font-bold mb-3">Lifetime Membership</h3>
          <p class="text-gray-600 text-sm">A one-time contribution for lifetime membership with no annual renewal required. This option ensures continuous support for ISGH and long-term community impact.</p>
        </div>
      </div>

      </div>
    </div>

    <div class="category-carousel-dots hidden sm:hidden justify-center gap-2 mt-5">
      <button type="button" class="category-dot active" data-index="0" aria-label="Go to category 1"></button>
      <button type="button" class="category-dot" data-index="1" aria-label="Go to category 2"></button>
      <button type="button" class="category-dot" data-index="2" aria-label="Go to category 3"></button>
    </div>
  </div>
</section>

  <!-- ===================== MEMBERSHIP CHANGES SECTION ===================== -->
  <!--<section class="w-full py-16 px-4 sm:px-8" style="background: linear-gradient(145deg, #e8ece8 0%, #dde5de 40%, #e4e9e4 100%);">
    <div class="max-w-6xl mx-auto"> -->

      <!-- Heading -->
      <!--<div class="text-center mb-3">
        <h2 class=" text-gray-900 text-4xl sm:text-5xl font-extrabold mb-4">Membership Changes</h2>
        <p class="text-gray-400 text-base font-medium tracking-wide">2024 Vs. 2025</p>
      </div> -->

      <!-- Table wrapper with horizontal scroll on small screens -->
      <!--<div class="mt-10 rounded-2xl overflow-hidden shadow-xl border border-gray-200 bg-white overflow-x-auto">
        <table class="w-full text-sm border-collapse min-w-[900px]">
          <thead>
            <tr>
              <th class="changes-th-main px-4 py-3 text-left text-xs font-bold text-gray-700 w-36" style="background:#c8d8e8;">Membership Type</th>
              <th class="changes-th px-4 py-3 text-center text-xs font-bold text-gray-700 w-24" style="background:#c8d8e8;">Category</th>
              <th class="changes-th px-4 py-3 text-center text-xs font-bold text-gray-700 w-24" style="background:#c8d8e8;">Parameter</th>
              <th class="changes-th px-4 py-3 text-center text-xs font-bold text-gray-700" style="background:#c8d8e8;">2024</th>
              <th class="changes-th px-4 py-3 text-center text-xs font-bold text-gray-700" style="background:#c8d8e8;">2025</th>
              <th class="changes-th px-4 py-3 text-left text-xs font-bold text-gray-700 w-72" style="background:#c8d8e8;">Changes to members signed up prior to 2025</th>
            </tr>
          </thead>
          <tbody> -->

            <!-- ===== ANNUAL MEMBERSHIP ===== -->
            <!--<tr style="background:#eaf3fb;">
              <td class="px-4 py-2 font-bold text-gray-800 text-xs border-t border-gray-200 align-middle" rowspan="6">Annual Membership</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200 align-middle" rowspan="2">Family</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Fee</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200">$75/year</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200">$40/year</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200" rowspan="2"></td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200"><strong>Husband, wife, parents, in-laws, children aged 15+</strong></td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Husband and wife only</td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200 align-middle" rowspan="2">Individual</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Fee</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200">$40/year</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200">$25/year</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200" rowspan="2">New 2025 rules apply</td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Must be <strong>15+ years old</strong></td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Must be <strong>18+ years old</strong></td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200 align-middle" rowspan="2">Student</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Fee</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200">$20/yr</td>
              <td class="px-4 py-2 text-center text-red-500 font-semibold text-xs border-t border-gray-200" rowspan="2">Abolished</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200" rowspan="2"></td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Must be <strong>15+ years old</strong></td>
            </tr>-->

            <!-- ===== CHECKOMATIC ===== -->
           <!-- <tr style="background:#fdf3e3;">
              <td class="px-4 py-2 font-bold text-gray-800 text-xs border-t-2 border-gray-300 align-middle" rowspan="4">Checkomatic*</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t-2 border-gray-300 align-middle" rowspan="2">Family</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t-2 border-gray-300">Fee</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t-2 border-gray-300">Monthly donation total upto June - $75<br/><strong>MIN $12.5/month req</strong></td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t-2 border-gray-300">Monthly donation total upto June - $40<br/><strong>min $10 a month required</strong></td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t-2 border-gray-300 leading-relaxed" rowspan="2">1. Will the extended family be automatically split into family & individuals if they had originally registered kids, parents etc ?? - <strong>NO. All family Members outside of Primary and thier spouse), were reomved from Membership and NOT given their own membership.</strong><br/><br/>2. Will the head of the household be allowed to pay for individuals in his family (kids, parents etc) on the same checkomatic account?- <strong>NO Separate Donation needed. cannot have 1 doantion ( e.g $20)</strong></td>
            </tr>
            <tr style="background:#fdf3e3;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200"><strong>Husband, wife, parents, in-laws, children aged 15+</strong></td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Husband and wife only; <strong>Family Members Removed</strong></td>
            </tr>
            <tr style="background:#fdf3e3;">
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200 align-middle" rowspan="2">Individual</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Fee</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Monthly donation total upto June - $40<br/>MIN $7.5/month required</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Same as above only 1 checkomatic is available</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200" rowspan="2"></td>
            </tr>
            <tr style="background:#fdf3e3;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Must be 18+</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200"></td>
            </tr> -->

            <!-- ===== 3-YEAR MEMBERSHIP ===== -->
           <!-- <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 font-bold text-gray-800 text-xs border-t-2 border-gray-300 align-middle" rowspan="4">3-Year Membership</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t-2 border-gray-300 align-middle" rowspan="2">Family</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t-2 border-gray-300">Fee</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t-2 border-gray-300">$180 one time payment to Membership</td>
              <td class="px-4 py-2 text-center font-semibold text-red-500 text-xs border-t-2 border-gray-300">N/A Not Offered Anymore</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t-2 border-gray-300 leading-relaxed" rowspan="2">1. Will the 3 yr membership bought between 2022 - 2024 be honored for all family members (kids, parents etc) till 2025 - 2027 ?- <strong>Yes if verified in election</strong><br/>2.</td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200"><strong>Husband, wife, parents, in-laws, children aged 15+</strong></td>
              <td class="px-4 py-2 text-xs border-t border-gray-200"></td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200 align-middle" rowspan="2">Individual</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Fee</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">$110 one time payment to Membership</td>
              <td class="px-4 py-2 text-center font-semibold text-red-500 text-xs border-t border-gray-200">N/A Not Offered Anymore</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200" rowspan="2">2.</td>
            </tr>
            <tr style="background:#eaf3fb;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Must be 18+</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200"></td>
            </tr> -->

            <!-- ===== 5-YEAR MEMBERSHIP ===== -->
            <!--<tr style="background:#f3ebf8;">
              <td class="px-4 py-2 font-bold text-gray-800 text-xs border-t-2 border-gray-300 align-middle" rowspan="4">5-Year Membership</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t-2 border-gray-300 align-middle" rowspan="2">Family</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t-2 border-gray-300">Fee</td>
              <td class="px-4 py-2 text-right text-gray-600 text-xs border-t-2 border-gray-300">$285</td>
              <td class="px-4 py-2 text-center font-semibold text-red-500 text-xs border-t-2 border-gray-300">N/A Not Offered Anymore</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t-2 border-gray-300 leading-relaxed" rowspan="2">1. Will the 5 yr membership bought between 2020 - 2024 be honored for all family members (kids, parents etc) till 2025 - 2029 ? <strong>Yes if verified in election</strong><br/>2.</td>
            </tr>
            <tr style="background:#f3ebf8;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200"><strong>Husband, wife, parents, in-laws, children aged 15+</strong></td>
              <td class="px-4 py-2 text-xs border-t border-gray-200"></td>
            </tr>
            <tr style="background:#f3ebf8;">
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200 align-middle" rowspan="2">Individual</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Fee</td>
              <td class="px-4 py-2 text-right text-gray-600 text-xs border-t border-gray-200">$170</td>
              <td class="px-4 py-2 text-center font-semibold text-red-500 text-xs border-t border-gray-200">N/A Not Offered Anymore</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200" rowspan="2">2.</td>
            </tr>
            <tr style="background:#f3ebf8;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Must be 18+</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200"></td>
            </tr> -->

            <!-- ===== LIFETIME MEMBERSHIP ===== -->
            <!--<tr style="background:#e8f5ee;">
              <td class="px-4 py-2 font-bold text-gray-800 text-xs border-t-2 border-gray-300 align-middle" rowspan="4">Lifetime Membership</td>
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t-2 border-gray-300 align-middle" rowspan="2">Family</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t-2 border-gray-300">Fee</td>
              <td class="px-4 py-2 text-right text-gray-600 text-xs border-t-2 border-gray-300">$1,500</td>
              <td class="px-4 py-2 text-right text-gray-600 text-xs border-t-2 border-gray-300">$1,500</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t-2 border-gray-300 leading-relaxed" rowspan="4">1. Will the lifetime membership be honored for all family members (kids, parents etc) forever ?- <strong>Parents yes by getting their own lifetime account, if verified election. Kids only until, they are 21 ( or above 18 due to new minimum requirement) since they would havee been removed anyways under old rules), if verified in election</strong></td>
            </tr>
            <tr style="background:#e8f5ee;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200"><strong>Husband, wife, parents, in-laws, children aged 15+</strong></td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Husband and wife only</td>
            </tr>
            <tr style="background:#e8f5ee;">
              <td class="px-4 py-2 text-center text-gray-600 text-xs border-t border-gray-200 align-middle" rowspan="2">Individual</td>
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Fee</td>
              <td class="px-4 py-2 text-right text-gray-600 text-xs border-t border-gray-200">N/A</td>
              <td class="px-4 py-2 text-right font-bold text-gray-800 text-xs border-t border-gray-200 underline">$1,000</td>
            </tr>
            <tr style="background:#e8f5ee;">
              <td class="px-4 py-2 text-center text-gray-500 text-xs border-t border-gray-200">Includes</td>
              <td class="px-4 py-2 text-xs border-t border-gray-200"></td>
              <td class="px-4 py-2 text-gray-600 text-xs border-t border-gray-200">Must be 18+</td>
            </tr>

          </tbody>
        </table>
      </div>

    </div>
  </section> -->

  <!-- ===================== FAQ SECTION ===================== -->
  <section class="w-full py-16 px-5 sm:px-8" style="background:linear-gradient(145deg,#e8ece8 0%,#dde5de 40%,#e4e9e4 100%);">
    <div class="max-w-3xl mx-auto">
      <div class="text-center mb-12">
        <h2 class=" text-gray-900 text-4xl sm:text-5xl font-extrabold">Frequently Ask Questions</h2>
      </div>
      <div class="flex flex-col gap-3" id="faqList">

        <div class="faq-item open">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>1. Why should I become an ISGH member?</span>
            <i class="faq-icon" id="icon0">−</i>
          </button>
          <div class="faq-answer">
            <p>Becoming a member means joining one of the largest Muslim communities in North America. Membership gives you voting rights, access to community services, and the opportunity to support and shape the future of your local masjid.</p>
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>2. Who is eligible for ISGH membership?</span>
            <i class="faq-icon">+</i>
          </button>
          <div class="faq-answer">
            <p>Any Muslim individual aged 18 or older residing in the Greater Houston area is eligible to become an ISGH member.</p>
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>3. What membership options are available?</span>
            <i class="faq-icon">+</i>
          </button>
          <div class="faq-answer">
            <p>ISGH offers three membership options: Annual, Check-O-Matic, and Lifetime. Each option provides membership access, with flexible ways to contribute and support the community.</p>
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>4. Do I need to renew my membership every year?</span>
            <i class="faq-icon">+</i>
          </button>
          <div class="faq-answer">
            <p>Annual memberships must be renewed each year. Check-O-Matic members must meet the required contribution threshold annually to maintain voting eligibility. Lifetime memberships do not require renewal.</p>
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>5. Can I register and pay online?</span>
            <i class="faq-icon">+</i>
          </button>
          <div class="faq-answer">
            <p>Yes, you can register and pay for your ISGH membership securely online. Simply click “Join ISGH Today” to get started. You will receive a confirmation once your membership is processed.</p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ===================== FOOTER ===================== -->
  <footer class="site-footer" style="background: linear-gradient(135deg, #0a5e3a 0%, #0d7a4e 50%, #12a060 100%); border-radius: 2rem 2rem 0 0; margin-top: 0; border-top: 10px solid white; border-left: 2px solid white; border-right: 2px solid white; box-shadow: 0 -4px 24px rgba(180,220,255,0.25), inset 0 1px 0 rgba(255,255,255,0.15);">
    <div class="footer-desktop max-w-6xl mx-auto px-8 sm:px-12 py-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full border-2 border-yellow-500 flex items-center justify-center bg-green-900 flex-shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo">
          </div>
        </div>
        <p class="text-green-200 text-sm leading-relaxed">
          Islamic Society of Greater Houston – Building community through faith, education, and service.
        </p>
      </div>

      <div>
        <h4 class="text-white font-bold text-base mb-4">Quick Links</h4>
        <ul class="flex flex-col gap-2.5">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> About Us</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Prayer Times</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Events</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Programs</li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-bold text-base mb-4">Quick Links</h4>
        <ul class="flex flex-col gap-2.5">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> About Us</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Prayer Times</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Events</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Programs</li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-bold text-base mb-4">Quick Links</h4>
        <ul class="flex flex-col gap-2.5">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> About Us</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Prayer Times</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Events</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Programs</li>
        </ul>
      </div>
    </div>

    <div class="footer-mobile hidden mx-auto w-full max-w-[390px] px-4 pt-10 pb-8">
      <div class="rounded-[34px] bg-[linear-gradient(135deg,#0a5e3a_0%,#0d7a4e_50%,#12a060_100%)] border border-white/20 shadow-[0_20px_45px_rgba(0,0,0,0.18)] px-4 py-5">
        <div class="flex justify-center">
          <div class="w-12 h-12 rounded-full border-2 border-yellow-500 flex items-center justify-center bg-green-900 flex-shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-8 h-8 object-contain">
          </div>
        </div>

        <form class="mt-5 flex flex-col gap-3">
          <input type="text" placeholder="Name" class="w-full rounded-[6px] bg-black/10 text-white placeholder:text-white/70 px-4 py-3 outline-none border border-black/10">
          <input type="email" placeholder="Email" class="w-full rounded-[6px] bg-black/10 text-white placeholder:text-white/70 px-4 py-3 outline-none border border-black/10">
          <textarea placeholder="Message" rows="4" class="w-full rounded-[6px] bg-black/10 text-white placeholder:text-white/70 px-4 py-3 outline-none border border-black/10 resize-none"></textarea>
          <button type="button" class="w-full rounded-[4px] bg-white text-black font-bold py-3.5 tracking-wide">SUBMIT</button>
        </form>

        <div class="mt-6 flex items-center justify-between gap-4">
          <h4 class="text-white font-bold text-sm uppercase tracking-wide">Follow Us</h4>
          <div class="flex items-center gap-3">
            <a href="#" class="w-10 h-10 rounded-full border border-white/70 flex items-center justify-center text-white font-bold">f</a>
            <a href="#" class="w-10 h-10 rounded-full border border-white/70 flex items-center justify-center text-white font-bold">t</a>
            <a href="#" class="w-10 h-10 rounded-full border border-white/70 flex items-center justify-center text-white font-bold">▶</a>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-6">
          <div class="text-left">
            <h4 class="text-white font-bold text-sm uppercase mb-3 tracking-wide">Contact Us</h4>
            <p class="text-green-100 text-sm leading-7">
              123 Simply Quidem<br>
              Soluta Lorem<br>
              The Park<br>
              AB 10000
            </p>
            <div class="mt-5">
              <h5 class="text-white font-bold text-sm uppercase tracking-wide">Phone No.</h5>
              <p class="text-green-100 text-sm">(123) 4567890</p>
            </div>
            <div class="mt-4">
              <h5 class="text-white font-bold text-sm uppercase tracking-wide">Email</h5>
              <p class="text-green-100 text-sm">abc123@gmail.com</p>
            </div>
          </div>

          <div class="text-left">
            <h4 class="text-white font-bold text-sm uppercase mb-3 tracking-wide">Quick Links</h4>
            <ul class="flex flex-col gap-3">
              <li class="text-green-100 text-sm">Text</li>
              <li class="text-green-100 text-sm">Ipsum</li>
              <li class="text-green-100 text-sm">Popular</li>
              <li class="text-green-100 text-sm">Services</li>
              <li class="text-green-100 text-sm">Matters</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="border-t border-green-700/50 mx-8 sm:mx-12">
      <div class="max-w-6xl mx-auto py-5 flex flex-col sm:flex-row items-center justify-between gap-2">
        <p class="text-green-300 text-xs">© 2026 Islamic Society of Greater Houston. All rights reserved.</p>
        <p class="text-green-400 text-xs">Built with ❤️ for the Houston Muslim Community</p>
      </div>
    </div>
  </footer>

  <!-- Mobile Menu -->
  <div id="mobileMenu" class="fixed inset-0 z-[200] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeMobileMenu()"></div>
    <div class="absolute top-0 right-0 w-72 h-full bg-[#0d1f14] flex flex-col p-6 shadow-2xl overflow-y-auto">
      <button onclick="closeMobileMenu()" class="self-end text-white/70 hover:text-white mb-6">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <nav class="flex flex-col gap-1">
        <a href="{{ route('home') }}" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-3 rounded-xl text-base transition-colors">Home</a>
        <a href="#" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-3 rounded-xl text-base transition-colors">Centers</a>
        <a href="#" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-3 rounded-xl text-base transition-colors">Donate</a>
        <a href="{{ route('join') }}" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-3 rounded-xl text-base transition-colors">Become a Member</a>
        <a href="{{ route('membership-verification') }}" class="text-white/90 hover:text-white hover:bg-white/10 px-4 py-3 rounded-xl text-base transition-colors">Verify Membership</a>
      </nav>
      <div class="mt-6 flex flex-col gap-3">
        <a href="#" class="bg-white/20 text-white text-center px-6 py-2.5 rounded-full font-semibold">Sign in</a>
        <a href="{{ route('join') }}" style="background:#00d084;" class="text-white text-center px-6 py-2.5 rounded-full font-semibold">Join Now</a>
      </div>
    </div>
  </div>

  <script>
    function openMobileMenu() { document.getElementById('mobileMenu').classList.remove('hidden'); document.body.style.overflow='hidden'; }
    function closeMobileMenu() { document.getElementById('mobileMenu').classList.add('hidden'); document.body.style.overflow=''; }

    (function initBenefitsCarousel() {
      const track = document.querySelector('.benefits-track');
      const slides = track ? Array.from(track.querySelectorAll('.benefit-slide')) : [];
      const prevBtn = document.getElementById('benefits-prev');
      const nextBtn = document.getElementById('benefits-next');
      const dots = Array.from(document.querySelectorAll('.benefit-dot'));

      if (!track || !slides.length || !prevBtn || !nextBtn || !dots.length) return;

      const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

      function getActiveIndex() {
        if (!isMobile()) return 0;

        const trackRect = track.getBoundingClientRect();
        const center = trackRect.left + trackRect.width / 2;
        let activeIndex = 0;
        let smallest = Infinity;

        slides.forEach((slide, index) => {
          const rect = slide.getBoundingClientRect();
          const slideCenter = rect.left + rect.width / 2;
          const distance = Math.abs(slideCenter - center);
          if (distance < smallest) {
            smallest = distance;
            activeIndex = index;
          }
        });

        return activeIndex;
      }

      function setActiveDot(index) {
        dots.forEach((dot, dotIndex) => {
          dot.classList.toggle('active', dotIndex === index);
        });
      }

      function scrollToIndex(index) {
        const clamped = Math.max(0, Math.min(index, slides.length - 1));
        slides[clamped].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        setActiveDot(clamped);
      }

      prevBtn.addEventListener('click', () => {
        scrollToIndex(Math.max(0, getActiveIndex() - 1));
      });

      nextBtn.addEventListener('click', () => {
        scrollToIndex(Math.min(slides.length - 1, getActiveIndex() + 1));
      });

      dots.forEach((dot, index) => {
        dot.addEventListener('click', () => scrollToIndex(index));
      });

      let rafId = null;
      track.addEventListener('scroll', () => {
        if (!isMobile()) return;
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          setActiveDot(getActiveIndex());
        });
      });

      window.addEventListener('resize', () => {
        setActiveDot(getActiveIndex());
      });

      setActiveDot(0);
    })();

    (function initCategoryCarousel() {
      const track = document.querySelector('.category-carousel-track');
      const slides = track ? Array.from(track.querySelectorAll('.category-slide')) : [];
      const prevBtn = document.getElementById('category-prev');
      const nextBtn = document.getElementById('category-next');
      const dots = Array.from(document.querySelectorAll('.category-dot'));

      if (!track || !slides.length || !prevBtn || !nextBtn || !dots.length) return;

      const isMobile = () => window.matchMedia('(max-width: 768px)').matches;
      let activeIndex = 0;

      function setActiveDot(index) {
        dots.forEach((dot, dotIndex) => {
          dot.classList.toggle('active', dotIndex === index);
        });
      }

      function renderCarousel() {
        if (isMobile()) {
          slides.forEach((slide, index) => {
            slide.classList.toggle('is-hidden-mobile', index !== activeIndex);
          });
        } else {
          slides.forEach((slide) => {
            slide.classList.remove('is-hidden-mobile');
          });
          activeIndex = 0;
        }

        setActiveDot(activeIndex);
      }

      function goToIndex(index) {
        activeIndex = Math.max(0, Math.min(index, slides.length - 1));
        renderCarousel();
      }

      prevBtn.addEventListener('click', () => goToIndex(activeIndex - 1));
      nextBtn.addEventListener('click', () => goToIndex(activeIndex + 1));
      dots.forEach((dot, index) => {
        dot.addEventListener('click', () => goToIndex(index));
      });

      window.addEventListener('resize', renderCarousel);

      renderCarousel();
    })();

    function toggleFaq(btn) {
      const item = btn.closest('.faq-item');
      const icon = btn.querySelector('.faq-icon');
      const isOpen = item.classList.contains('open');

      // Close all
      document.querySelectorAll('.faq-item').forEach(el => {
        el.classList.remove('open');
        el.querySelector('.faq-icon').textContent = '+';
      });

      // Open clicked if it was closed
      if (!isOpen) {
        item.classList.add('open');
        icon.textContent = '−';
      }
    }
  </script>
</body>
</html>

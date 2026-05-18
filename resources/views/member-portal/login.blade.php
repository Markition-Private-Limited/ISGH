<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Member Login — ISGH</title>
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
      --green-dark:  #064e36;
      --green-mid:   #10b981;
      --green-light: #e6f4ee;
      --green-ring:  rgba(13,122,85,0.15);
      --bg:          #f5f5f5;
      --text:        #111;
      --text-muted:  #8a9690;
      --border:      #e2e8e4;
    }

    html, body {
      height: 100%;
      font-family: 'SF Pro regular', 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Main ── */
    .main {
      flex: 1;
      min-height: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      width: 100%;
    }

    .cards-wrapper {
      width: 100%;
      max-width: 1180px;
      display: grid;
      grid-template-columns: 1.3fr 0.7fr;
      gap: 28px;
      align-items: stretch;
    }

    /* ── Left card ── */
    .left-card {
      background: #ffffff;
      background-image: linear-gradient(to top right, #c8f0dc 0%, #ffffff 50%);
      border-radius: 24px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.06);
      padding: clamp(32px, 4vw, 56px);
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-height: 460px;
    }

    .form-inner {
      width: 100%;
      max-width: 380px;
      margin: 0 auto;
    }

    .login-box {
      text-align: center;
      margin-bottom: 32px;
    }
    .login-box h1 {
      font-family: 'DM Sans', sans-serif;
      color: var(--text);
      line-height: 1.15;
      font-size: 40px;
      font-weight: 700;
      letter-spacing: -0.5px;
      margin-bottom: 16px;
    }
    .login-box p {
      font-size: 16px;
      color: var(--text-muted);
      font-weight: 400;
    }

    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 0 0 32px;
    }

    /* ── Fields ── */
    .field {
      position: relative;
      margin-bottom: 24px;
    }
    .field label {
      position: absolute;
      top: -8px;
      left: 14px;
      background: #fff;
      padding: 0 6px;
      font-size: 12px;
      font-weight: 500;
      letter-spacing: 0;
      text-transform: none;
      color: #4a5854;
      font-family: 'DM Sans', sans-serif;
    }
    .field label span { color: #ef4444; margin-left: 2px; }
    .field input[type="email"],
    .field input[type="text"] {
      width: 100%;
      border: 1.5px solid #d6ddd9;
      border-radius: 14px;
      padding: 14px 16px;
      font-size: 15px;
      font-family: 'DM Sans', sans-serif;
      color: var(--text);
      background: #fff;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .field input::placeholder { color: #b0bab5; }
    .field input:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px var(--green-ring);
    }
    .field input.has-error { border-color: #ef4444 !important; }

    /* ── OTP sent banner ── */
    .otp-sent-msg {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #15803d;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 16px;
      display: flex;
      align-items: flex-start;
      gap: 8px;
      line-height: 1.5;
    }
    .otp-sent-msg svg { flex-shrink: 0; margin-top: 2px; }

    /* ── Error banner ── */
    .error-msg {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 16px;
      display: none;
    }

    /* ── OTP section ── */
    .otp-section { display: none; }
    .otp-section.visible { display: block; }

    .form-title {
      font-size: 22px;
      font-weight: 600;
      color: var(--text);
      text-align: center;
      margin-bottom: 6px;
      font-family: 'DM Sans', sans-serif;
    }
    .form-sub {
      font-size: 14px;
      color: var(--text-muted);
      text-align: center;
      margin-bottom: 20px;
      font-weight: 400;
    }

    .otp-label {
      font-size: 13px;
      font-weight: 500;
      color: #374151;
      text-align: center;
      display: block;
      margin-bottom: 14px;
    }

    .otp-boxes {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-bottom: 14px;
    }
    .otp-box {
      width: 44px;
      height: 52px;
      border: 1.5px solid #d6ddd9;
      border-radius: 10px;
      font-size: 20px;
      font-weight: 600;
      text-align: center;
      color: var(--text);
      background: #fff;
      outline: none;
      font-family: 'DM Sans', sans-serif;
      transition: border-color 0.2s, box-shadow 0.2s;
      caret-color: transparent;
    }
    .otp-box:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px var(--green-ring);
    }
    .otp-box.filled { border-color: var(--green); background: #f0fdf4; }
    .otp-box.error  { border-color: #ef4444 !important; background: #fef2f2; }

    .resend-row {
      text-align: center;
      font-size: 13px;
      color: #6b7280;
      margin-bottom: 18px;
    }
    .resend-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      color: var(--green);
      text-decoration: underline;
      padding: 0;
    }
    .resend-btn:disabled { color: #9ca3af; text-decoration: none; cursor: default; }
    .resend-timer { font-weight: 600; color: #374151; }

    /* ── Primary button ── */
    .btn-primary {
      width: 100%;
      margin-top: 20px;
      padding: 16px 24px;
      background: var(--green-dark);
      color: #fff;
      border: none;
      border-radius: 999px;
      font-size: 15px;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      letter-spacing: 0.02em;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s, opacity 0.15s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(6, 78, 54, 0.18);
    }
    .btn-primary:hover:not(:disabled) {
      background: #075a3f;
      transform: translateY(-1px);
      box-shadow: 0 8px 22px rgba(6, 78, 54, 0.28);
    }
    .btn-primary:active:not(:disabled) { transform: translateY(0); box-shadow: 0 4px 14px rgba(6, 78, 54, 0.18); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

    .btn-back {
      width: 100%;
      background: none;
      border: none;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      color: #6b7280;
      cursor: pointer;
      padding: 8px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      transition: color 0.15s;
      margin-top: 8px;
    }
    .btn-back:hover { color: #374151; }

    .spinner {
      width: 17px; height: 17px;
      border: 2.5px solid rgba(255,255,255,0.35);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      display: none;
      flex-shrink: 0;
    }
    .btn-primary.loading .spinner { display: block; }
    .btn-primary.loading .btn-text { opacity: 0; position: absolute; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Right card ── */
    .right-card {
      border-radius: 24px;
      overflow: hidden;
      position: relative;
      background: var(--green);
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
      min-height: 460px;
    }
    .right-card img {
      position: absolute;
      inset: 0;
      width: 100%; height: 100%;
      object-fit: cover;
      object-position: center center;
      display: block;
    }
    .ring {
      position: absolute;
      border-radius: 50%;
      border: 3px solid rgba(255,255,255,0.2);
      pointer-events: none;
    }
    .r1 { width: 250px; height: 250px; top: -70px; left: -70px; }
    .r2 { width: 330px; height: 330px; top: 50px; right: -110px; }
    .r3 { width: 180px; height: 180px; bottom: -45px; left: 15px; }

    .mobile-join-visual { display: none; }

    /* ── Navbar ── */
    .navbar-glass {
      background: rgba(10,10,10,0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    /* ── Responsive ── */
    @media (max-width: 960px) {
      .left-card { padding: 40px 32px; min-height: auto; }
      .cards-wrapper { gap: 20px; }
    }

    @media (max-width: 860px) {
      .cards-wrapper {
        grid-template-columns: 1fr;
        max-width: 520px;
        gap: 20px;
      }
      .right-card { min-height: 320px; }
      .left-card  { padding: 40px 32px; min-height: auto; }
    }

    @media (max-width: 640px) {
      .nav-pill { display: none; }
      .main { padding: 16px 16px 32px; align-items: flex-start; }
      .cards-wrapper { max-width: 100%; gap: 16px; }
      .left-card {
        padding: 28px 20px;
        border-radius: 20px;
      }
      .form-inner { max-width: 100%; }
      .login-box { margin-bottom: 24px; }
      .login-box h1 { font-size: 28px; margin-bottom: 12px; }
      .login-box p  { font-size: 14px; }
      .divider      { margin-bottom: 24px; }
      .field        { margin-bottom: 20px; }
      .field input  { padding: 13px 14px; font-size: 14px; }
      .btn-primary  { padding: 14px 20px; font-size: 14px; margin-top: 16px; }
      .right-card   { display: none; }
      .mobile-join-visual {
        display: block;
        margin-top: 8px;
        border-radius: 20px;
        overflow: hidden;
        background: linear-gradient(135deg, #0c7b52 0%, #0b5f40 100%);
        padding: 6px;
      }
      .mobile-join-visual .join-visual-frame {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        min-height: 240px;
        background: #0c7b52;
      }
      .mobile-join-visual img {
        width: 100%; height: 240px;
        object-fit: cover;
        object-position: center top;
        filter: grayscale(100%);
        display: block;
      }
    }

    @media (max-width: 380px) {
      .main { padding-left: 12px; padding-right: 12px; }
      .left-card { padding: 24px 18px; }
      .login-box h1 { font-size: 24px; }
      .login-box p  { font-size: 13px; }
    }
  </style>
</head>
<body class="page">

  {{-- ── Navbar ── --}}
  <header class="w-full pt-6 sm:pt-8 relative z-50 px-4 sm:px-8 md:px-12 lg:px-16">
    <div class="flex items-center gap-3 max-w-[1280px] mx-auto">
      <a href="{{ route('home') }}" class="hidden lg:block shrink-0" aria-label="ISGH Home">
        <div class="w-14 h-14 rounded-full border border-[#c8a84b] flex items-center justify-center bg-[#1a4a2e] shadow-lg">
          <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-10 h-10 object-contain">
        </div>
      </a>

      <nav class="nav-pill hidden lg:flex navbar-glass rounded-full pl-8 pr-2 py-2 items-center gap-8 ml-auto">
        <div class="flex items-center gap-7">
          <a href="{{ route('home') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Home</a>
          <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Centers</a>
          <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Donate</a>
          <a href="{{ route('join') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Become a Member</a>
          <a href="{{ route('membership-verification') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Verify Membership Status</a>
        </div>
        <div class="flex items-center gap-3">
          <a href="{{ route('member-portal.login') }}" class="bg-white/20 hover:bg-white/30 text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors inline-block text-center">Sign in</a>
          <a href="{{ route('join') }}" style="background:#00d084;" class="hover:bg-[#00b870] text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors shadow-md inline-block text-center">Join Now</a>
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

  {{-- ── Main ── --}}
  <main class="main">
    <div class="cards-wrapper">

      {{-- Left card --}}
      <div class="left-card">
        <div class="form-inner">

          <div class="login-box">
            <h1>Membership Login</h1>
            <p>Login to access your member portal</p>
          </div>

          {{-- Mobile image --}}
          <div class="mobile-join-visual">
            <div class="join-visual-frame">
              <img src="{{ asset('images/Frame 116.png') }}" alt="Community" />
            </div>
          </div>

          {{-- Error banner --}}
          <div id="js-error" class="error-msg"></div>

          {{-- ── STEP 1: Email ── --}}
          <div id="step-email">
            <hr class="divider"/>

            <div class="field">
              <label>Email <span>*</span></label>
              <input type="email" id="email" placeholder="ali44@gmail.com" autocomplete="username" autofocus />
            </div>

            <button type="button" id="btn-send-otp" class="btn-primary">
              <div class="spinner"></div>
              <span class="btn-text">Send OTP</span>
            </button>
          </div>

          {{-- ── STEP 2: OTP ── --}}
          <div id="step-otp" class="otp-section">

            <div class="otp-sent-msg" id="otp-sent-banner">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
              <span>
                OTP sent successfully!<br>
                <small>Check your email at <strong id="masked-email"></strong> for the verification code.</small>
              </span>
            </div>

            <p class="form-title">Verify Your Identity</p>
            <p class="form-sub">Enter the 6-digit code sent to your email</p>
            <hr class="divider"/>

            <span class="otp-label">Enter Verification Code</span>

            <div class="otp-boxes">
              <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="0" />
              <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="1" />
              <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="2" />
              <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="3" />
              <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="4" />
              <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="5" />
            </div>

            <div class="resend-row">
              Didn't receive the code?
              <button type="button" class="resend-btn" id="btn-resend" disabled>
                Resend in <span class="resend-timer" id="resend-timer">00:59</span>
              </button>
            </div>

            <button type="button" id="btn-verify-otp" class="btn-primary">
              <div class="spinner"></div>
              <span class="btn-text">Verify &amp; Continue</span>
            </button>

            <button type="button" id="btn-back" class="btn-back">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <polyline points="15 18 9 12 15 6"/>
              </svg>
              Back to Email
            </button>

          </div>

        </div>
      </div>

      {{-- Right card --}}
      <div class="right-card">
        <div class="ring r1"></div>
        <div class="ring r2"></div>
        <div class="ring r3"></div>
        <img src="{{ asset('images/Frame 116.png') }}" alt="Community hands" />
      </div>

    </div>
  </main>

{{-- Mobile menu --}}
<div id="mobileMenu" class="fixed inset-0 z-[200] hidden">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeMobileMenu()"></div>
  <div class="absolute top-0 right-0 w-72 h-full bg-[#0d1f14] flex flex-col p-6 shadow-2xl overflow-y-auto">
    <button onclick="closeMobileMenu()" class="self-end text-white/70 hover:text-white mb-6">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
    <nav class="flex flex-col gap-1">
      <a href="{{ route('home') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Home</a>
      <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Centers</a>
      <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Donate</a>
      <a href="{{ route('join') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Become a Member</a>
      <a href="{{ route('membership-verification') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Verify Membership</a>
    </nav>
    <div class="mt-6 flex flex-col gap-3">
      <a href="{{ route('member-portal.login') }}" class="bg-white/20 text-white text-center px-6 py-2.5 rounded-full font-semibold">Sign in</a>
      <a href="{{ route('join') }}" style="background:#00d084;" class="text-white text-center px-6 py-2.5 rounded-full font-semibold">Join Now</a>
    </div>
  </div>
</div>

<script>
function openMobileMenu()  { document.getElementById('mobileMenu').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeMobileMenu() { document.getElementById('mobileMenu').classList.add('hidden');    document.body.style.overflow = ''; }

(function () {
  const csrf       = document.querySelector('meta[name="csrf-token"]').content;
  const emailInput = document.getElementById('email');
  const stepEmail  = document.getElementById('step-email');
  const stepOtp    = document.getElementById('step-otp');
  const btnSend    = document.getElementById('btn-send-otp');
  const btnVerify  = document.getElementById('btn-verify-otp');
  const btnBack    = document.getElementById('btn-back');
  const btnResend  = document.getElementById('btn-resend');
  const jsError    = document.getElementById('js-error');
  const maskedEl   = document.getElementById('masked-email');
  const otpBoxes   = Array.from(document.querySelectorAll('.otp-box'));
  const timerEl    = document.getElementById('resend-timer');

  let resendInterval = null;

  function showError(msg) { jsError.textContent = msg; jsError.style.display = ''; }
  function clearError()   { jsError.textContent = ''; jsError.style.display = 'none'; }

  function setLoading(btn, state) {
    btn.classList.toggle('loading', state);
    btn.disabled = state;
  }

  function getOtp() { return otpBoxes.map(b => b.value).join(''); }

  function clearBoxes() {
    otpBoxes.forEach(b => { b.value = ''; b.classList.remove('filled', 'error'); });
  }

  otpBoxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '').slice(-1);
      box.classList.toggle('filled', box.value !== '');
      otpBoxes.forEach(b => b.classList.remove('error'));
      if (box.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
    });
    box.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !box.value && i > 0) {
        otpBoxes[i - 1].value = '';
        otpBoxes[i - 1].classList.remove('filled');
        otpBoxes[i - 1].focus();
      }
      if (e.key === 'Enter') btnVerify.click();
    });
    box.addEventListener('paste', e => {
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      e.preventDefault();
      text.split('').slice(0, 6).forEach((ch, j) => {
        if (otpBoxes[j]) { otpBoxes[j].value = ch; otpBoxes[j].classList.add('filled'); }
      });
      const next = otpBoxes[Math.min(text.length, 5)];
      if (next) next.focus();
    });
  });

  function startTimer(secs) {
    clearInterval(resendInterval);
    btnResend.disabled = true;
    let remaining = secs;
    function tick() {
      const m = String(Math.floor(remaining / 60)).padStart(2, '0');
      const s = String(remaining % 60).padStart(2, '0');
      timerEl.textContent = m + ':' + s;
      if (remaining <= 0) {
        clearInterval(resendInterval);
        btnResend.disabled = false;
        btnResend.innerHTML = '<span style="text-decoration:underline">Resend code</span>';
      }
      remaining--;
    }
    tick();
    resendInterval = setInterval(tick, 1000);
  }

  async function doSendOtp() {
    clearError();
    const email = emailInput.value.trim();
    if (!email) { emailInput.focus(); showError('Please enter your email address.'); return; }

    setLoading(btnSend, true);
    try {
      const res  = await fetch('{{ route("member-portal.send-otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ email }),
      });
      const data = await res.json();
      if (!data.success) { showError(data.message || 'Something went wrong.'); return; }

      maskedEl.textContent = data.masked_email || email;
      stepEmail.style.display = 'none';
      stepOtp.classList.add('visible');
      clearBoxes();
      otpBoxes[0].focus();
      startTimer(59);
    } catch { showError('Network error. Please try again.'); }
    finally  { setLoading(btnSend, false); }
  }

  btnSend.addEventListener('click', doSendOtp);
  emailInput.addEventListener('keydown', e => { if (e.key === 'Enter') doSendOtp(); });

  btnVerify.addEventListener('click', async () => {
    clearError();
    const otp = getOtp();
    if (otp.length < 6) {
      otpBoxes.forEach(b => { if (!b.value) b.classList.add('error'); });
      showError('Please enter all 6 digits of your verification code.');
      return;
    }

    setLoading(btnVerify, true);
    try {
      const res  = await fetch('{{ route("member-portal.verify-otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ otp }),
      });
      const data = await res.json();
      if (!data.success) {
        otpBoxes.forEach(b => b.classList.add('error'));
        showError(data.message || 'Incorrect code. Please try again.');
        return;
      }
      window.location.href = data.redirect;
    } catch { showError('Network error. Please try again.'); }
    finally  { setLoading(btnVerify, false); }
  });

  btnResend.addEventListener('click', async () => {
    if (btnResend.disabled) return;
    clearError();
    btnResend.disabled = true;
    try {
      const res  = await fetch('{{ route("member-portal.resend-otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      });
      const data = await res.json();
      if (!data.success) { showError(data.message || 'Failed to resend.'); btnResend.disabled = false; return; }
      clearBoxes();
      otpBoxes[0].focus();
      startTimer(59);
    } catch { showError('Network error. Please try again.'); btnResend.disabled = false; }
  });

  btnBack.addEventListener('click', () => {
    clearError();
    clearInterval(resendInterval);
    stepOtp.classList.remove('visible');
    stepEmail.style.display = '';
    emailInput.focus();
  });
})();
</script>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verify OTP - ISGH</title>
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
      --green-ring:  rgba(13,122,85,0.15);
      --bg:          #e8edea;
    }

    html, body {
      height: 100%;
      font-family: 'SF Pro regular', 'DM Sans', sans-serif;
      background: rgba(249, 249, 249, 1);
    }

    .page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .header {
      width: 100%;
      padding: 1rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      background: var(--bg);
    }

    .logo {
      width: 46px; height: 46px;
      border-radius: 50%;
      background: #1a2119;
      border: 2px solid var(--green-mid);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Playfair Display', serif;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--green-mid);
      flex-shrink: 0;
    }
        .nav{
        border: 10px solid #ffff;
      }
    .nav-pill {
      background: #1a2119;
      border-radius: 999px;
      display: flex;
      align-items: center;
      gap: 0.1rem;
      padding: 0.32rem 0.45rem;
    }
    .nav-pill a {
      font-size: 0.81rem;
      color: #b0bfb6;
      padding: 0.36rem 0.8rem;
      border-radius: 999px;
      text-decoration: none;
      white-space: nowrap;
      transition: background 0.18s, color 0.18s;
    }
    .nav-pill a:hover { background: #2b3a30; color: #fff; }
    .nav-pill .si {
      background: #2b3a30;
      color: #dde8e2 !important;
      font-weight: 500 !important;
    }
    .nav-pill .vf {
      background: var(--green-mid) !important;
      color: #fff !important;
      font-weight: 600 !important;
    }
    .nav-pill .vf:hover { background: var(--green) !important; }

    .mob-nav { display: none; gap: 0.5rem; }
    .mob-nav a {
      font-size: 0.8rem; font-weight: 600;
      padding: 0.38rem 1rem; border-radius: 999px; text-decoration: none;
    }
    .mob-nav .si { background: #1a2119; color: #dde8e2; }
    .mob-nav .vf { background: var(--green-mid); color: #fff; }

    .main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 2rem 2.5rem;
    }

    .cards-wrapper {
      width: 100%;
      /* max-width: 1000px; */
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      align-items: stretch;
    }

    .left-card {
      background: #ffffff;
      background-image: linear-gradient(
        to top right,
        #b8f0d8 0%,
        #ffffff 45%
      );
      border-radius: 1.5rem;
      box-shadow: 0 4px 40px rgba(0,0,0,0.08);
      padding: 2.8rem 2.8rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .join-box {
      border: 1.5px solid #e0e7e3;
      border-radius: 1rem;
      padding: 1.3rem 1.5rem 1.1rem;
      text-align: center;
      margin-bottom: 65px;
     background: #cacaca33
    }
    .join-box h1 {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      font-weight: 700;
      color: #111;
      margin-bottom: 0.3rem;
      line-height: 1.15;
    }
    .join-box p { font-size: 0.87rem; color: #7a8a82; }

    .divider {
      border: none;
      border-top: 1px solid #dde5e0;
      margin: 0 0 1.5rem;
    }

    .form-title {
      font-size: 1.15rem;
      font-weight: 600;
      color: #111;
      text-align: center;
      margin-bottom: 0.22rem;
    }
    .form-sub {
      font-size: 0.83rem;
      color: #94a49b;
      text-align: center;
      margin-bottom: 1.8rem;
    }

    .field {
      position: relative;
      margin-bottom: 1.15rem;
    }
    .field label {
      position: absolute;
      top: -0.5rem;
      left: 0.85rem;
      background: white;
      padding: 0 0.28rem;
      font-size: 0.68rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--green);
    }
    .field label span { color: #ef4444; margin-left: 1px; }
    .field input {
      width: 100%;
      border: 1.5px solid #cdd6d1;
      border-radius: 0.6rem;
      padding: 0.75rem 1rem;
      font-size: 0.91rem;
      font-family: 'DM Sans', sans-serif;
      color: #111;
      background: #fff;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .field input::placeholder { color: #a8b5ae; }
    .field input:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px var(--green-ring);
    }
    .left-card .field label { background: transparent; }
    .left-card .field input { background: #fff; }

    .btn-otp {
      width: 100%;
      margin-top: 0.35rem;
      padding: 0.85rem;
      background: var(--green-dark);
      color: #fff;
      border: none;
      border-radius: 999px;
      font-size: 0.93rem;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      letter-spacing: 0.03em;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    }
    .btn-otp:hover {
      background: var(--green);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(13,122,85,0.28);
    }
    .btn-otp:active { transform: translateY(0); box-shadow: none; }

    .right-card {
      border-radius: 1.5rem;
      overflow: hidden;
      position: relative;
      background: var(--green);
      box-shadow: 0 4px 40px rgba(0,0,0,0.10);
      min-height: 500px;
      border :10px solid #ffff;
      /* margin-right: -70px; */
    }
    .right-card img {
      position: absolute;
      inset: 0;
      width: 100%; height: 100%;
      object-fit: cover;
      object-position: center 10%;
      filter: grayscale(100%);
      mix-blend-mode: luminosity;
      display: block;
    }
    .mobile-otp-visual {
      display: none;
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

    @media (max-width: 860px) {
      .cards-wrapper {
        grid-template-columns: 1fr;
        max-width: 480px;
      }
      .right-card { min-height: 300px; }
      .left-card { padding: 2.2rem 2rem; }
    }
    @media (max-width: 640px) {
      .header { padding: 0.8rem 1rem; }
      .nav-pill { display: none; }
      .mob-nav  { display: flex; }
      .main { padding: 0.95rem 0.85rem 1.6rem; align-items: flex-start; }
      .cards-wrapper { max-width: 100%; }
      .left-card {
        padding: 0.95rem 0.85rem 1rem;
        border-radius: 1.4rem;
        border: 8px solid rgba(247,247,247,1);
        box-shadow: 0 10px 28px rgba(0,0,0,0.14);
      }
      .join-box {
        margin-bottom: 0.95rem;
        padding: 0.9rem 1rem 0.78rem;
        border-radius: 0.9rem;
      }
      .join-box h1 { font-size: 1.65rem; }
      .join-box p { font-size: 0.78rem; }
      .mobile-otp-visual {
        display: block;
        margin: 0.1rem 0 1rem;
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid rgba(224,231,227,0.95);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        background: linear-gradient(135deg, #0c7b52 0%, #0b5f40 100%);
        padding: 0.3rem;
      }
      .mobile-otp-visual .join-visual-frame {
        position: relative;
        overflow: hidden;
        border-radius: 0.85rem;
        min-height: 240px;
        background: #0c7b52;
      }
      .mobile-otp-visual img {
        width: 100%;
        height: 240px;
        object-fit: cover;
        object-position: center 18%;
        filter: grayscale(100%);
        display: block;
      }
      .form-title { font-size: 1.08rem; margin-bottom: 0.18rem; }
      .form-sub { font-size: 0.76rem; margin-bottom: 0.95rem; }
      .otp-grid {
        gap: 0.3rem;
        margin-bottom: 1.15rem;
        flex-wrap: nowrap;
      }
      .otp-input {
        width: 39px !important;
        height: 39px !important;
        font-size: 0.95rem !important;
      }
      .right-card { display: none; }
      .btn-otp { padding: 0.82rem 1rem; font-size: 0.92rem; border-width: 4px; }
      .btn-change {
        padding: 0.85rem 1rem !important;
        font-size: 0.88rem !important;
        border-width: 4px !important;
      }
    }
    @media (max-width: 380px) {
      .main { padding-left: 0.65rem; padding-right: 0.65rem; }
      .left-card { padding: 0.85rem 0.7rem 0.95rem; }
      .join-box h1 { font-size: 1.45rem; }
      .join-box p { font-size: 0.72rem; }
      .form-title { font-size: 1rem; }
      .form-sub { font-size: 0.71rem; }
      .mobile-otp-visual img,
      .mobile-otp-visual .join-visual-frame { height: 214px; min-height: 214px; }
      .otp-grid { gap: 0.25rem; }
      .otp-input {
        width: 36px !important;
        height: 36px !important;
        font-size: 0.9rem !important;
      }
    }
    .navbar-glass {
      background: rgba(10, 10, 10, 0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
    }

    /* OTP grid wraps on very small screens */
    .otp-grid {
      display: flex;
      justify-content: center;
      gap: 0.75rem;
      margin-bottom: 1.75rem;
      flex-wrap: wrap;
    }
  </style>
</head>
<body class="min-h-screen flex flex-col">
<div class="" style="border: 10px solid #ffff;border-radius: 40px; background: rgba(248, 248, 248, 1);">
  <header class="w-full pt-6 sm:pt-8 relative z-50 px-4 sm:px-8 md:px-16 lg:px-24">
    <div class="flex items-center gap-3">
      <a href="{{ route('home') }}" class="hidden lg:block shrink-0" aria-label="ISGH Home">
        <div class="w-14 h-14 rounded-full border border-[#c8a84b] flex items-center justify-center bg-[#1a4a2e] shadow-lg">
          <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-10 h-10 object-contain">
        </div>
      </a>

      <nav class="hidden lg:flex navbar-glass rounded-full pl-8 pr-2 py-2 items-center gap-8 ml-auto">
        <div class="flex items-center gap-7">
          <a href="{{ route('home') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Home</a>
          <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Centers</a>
          <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Donate</a>
          <a href="{{ route('join') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Become a Member</a>
          <a href="{{ route('membership-verification') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Verify Membership Status</a>
        </div>

        <div class="flex items-center gap-3">
          <a href="#" class="bg-white/20 hover:bg-white/30 text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors inline-block text-center">Sign in</a>
          <a href="{{ route('verify-otp') }}" style="background: #00d084;" class="hover:bg-[#00b870] text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors shadow-md inline-block text-center">Verify Now</a>
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

  <main class="main">
    <div class="cards-wrapper">
      <div class="left-card">
        <div class="join-box">
          <h1>Join ISGH</h1>
          <p>Become part of our community</p>
        </div>

        <div class="mobile-otp-visual">
          <div class="join-visual-frame">
            <img
              src="{{ asset('images/couple.jpg') }}"
              alt="Family together"
              loading="lazy"
            />
          </div>
        </div>

        <div style="margin-top: 2rem;">
          <p class="form-title">Verify Email OTP</p>
          <p class="form-sub">Enter the 6-digit code sent to your email address</p>

          @if (session('success'))
            <div style="background: #f0fdf4; border: 1px solid #10b981; border-radius: 0.5rem; padding: 0.9rem 1rem; margin: 1.5rem 0; display: flex; align-items: flex-start; gap: 0.75rem;">
              <svg style="width: 20px; height: 20px; color: #047857; flex-shrink: 0; margin-top: 0.1rem;" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <div style="flex: 1;">
                <p style="font-family: 'SF Pro bold'; color: #047857; font-size: 0.9rem; margin-bottom: 0.2rem;">Success</p>
                <p style="font-size: 0.8rem; color: #047857; font-family: 'SF Pro regular';">{{ session('success') }}</p>
              </div>
            </div>
          @endif

          @if (session('error'))
            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; padding: 0.9rem 1rem; margin: 1.5rem 0; color: #991b1b;">
              {{ session('error') }}
            </div>
          @endif

          @if ($errors->has('otp'))
            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; padding: 0.9rem 1rem; margin: 1.5rem 0; color: #991b1b;">
              {{ $errors->first('otp') }}
            </div>
          @endif

          <p style="text-align: center; font-family: 'SF Pro bold'; color: #1a1a1a; margin: 2rem 0 1.25rem; font-size: 0.95rem;">Enter Verification Code</p>

          <!-- OTP Input Fields -->
          <form action="{{ route('otp.verify') }}" method="POST" onsubmit="combineOtpFields(event)">
            @csrf
            <div class="otp-grid">
              <input type="text" inputmode="numeric" maxlength="1" class="otp-input" style="width: 54px; height: 54px; border: 1px solid #d1d5db; border-radius: 0.5rem; text-align: center; font-size: 1.25rem; font-family: 'SF Pro bold'; color: #1a1a1a; outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: #ffffff;" required />
              <input type="text" inputmode="numeric" maxlength="1" class="otp-input" style="width: 54px; height: 54px; border: 1px solid #d1d5db; border-radius: 0.5rem; text-align: center; font-size: 1.25rem; font-family: 'SF Pro bold'; color: #1a1a1a; outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: #ffffff;" required />
              <input type="text" inputmode="numeric" maxlength="1" class="otp-input" style="width: 54px; height: 54px; border: 1px solid #d1d5db; border-radius: 0.5rem; text-align: center; font-size: 1.25rem; font-family: 'SF Pro bold'; color: #1a1a1a; outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: #ffffff;" required />
              <input type="text" inputmode="numeric" maxlength="1" class="otp-input" style="width: 54px; height: 54px; border: 1px solid #d1d5db; border-radius: 0.5rem; text-align: center; font-size: 1.25rem; font-family: 'SF Pro bold'; color: #1a1a1a; outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: #ffffff;" required />
              <input type="text" inputmode="numeric" maxlength="1" class="otp-input" style="width: 54px; height: 54px; border: 1px solid #d1d5db; border-radius: 0.5rem; text-align: center; font-size: 1.25rem; font-family: 'SF Pro bold'; color: #1a1a1a; outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: #ffffff;" required />
              <input type="text" inputmode="numeric" maxlength="1" class="otp-input" style="width: 54px; height: 54px; border: 1px solid #d1d5db; border-radius: 0.5rem; text-align: center; font-size: 1.25rem; font-family: 'SF Pro bold'; color: #1a1a1a; outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: #ffffff;" required />
            </div>

            <input type="hidden" name="otp" id="otp-combined" />

            <!-- Resend Timer -->
            <p style="text-align: center; font-size: 0.95rem; color: #4b5563; font-family: 'SF Pro regular'; margin-bottom: 2rem;">
              Didn't receive the code? <span style="font-family: 'SF Pro bold'; color: #1a1a1a;">Resend in <span id="resend-timer">00:59</span></span>
            </p>

            <!-- Action Buttons -->
            <button type="submit" class="btn-otp " style="margin-top: 0; border: 5px solid #fff;">Verify & Continue</button>
            <button type="button" class="btn-change" style="width: 100%; margin-top: 0.75rem; padding: 1rem; background: #f3f4f6; color: #4b5563; border: 5px solid #fff; border-radius: 999px; font-size: 0.95rem; font-family: 'SF Pro bold', sans-serif; cursor: pointer; transition: background 0.2s, transform 0.15s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onclick="goToJoin()">
              <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
              Change Email/Phone
            </button>
          </form>
        </div>
      </div>

      <div class="right-card">
        <div class="ring r1"></div>
        <div class="ring r2"></div>
        <div class="ring r3"></div>
        <img
          src="{{ asset('images/couple.jpg') }}"
          alt="Family together"
          loading="lazy"
        />
      </div>
    </div>
  </main>
</div>

<script>
  // OTP Input Handler
  const otpInputs = document.querySelectorAll('.otp-input');

  otpInputs.forEach((input, index) => {
    // Allow only single digit; auto-advance on input
    input.addEventListener('input', () => {
      // Strip non-digits, keep only last character typed
      input.value = input.value.replace(/[^0-9]/g, '').slice(-1);
      if (input.value.length === 1 && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace') {
        if (input.value !== '') {
          input.value = '';
        } else if (index > 0) {
          otpInputs[index - 1].focus();
          otpInputs[index - 1].value = '';
        }
        e.preventDefault();
      } else if (e.key === 'ArrowLeft' && index > 0) {
        otpInputs[index - 1].focus();
      } else if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });

    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const digits = e.clipboardData.getData('text').replace(/[^0-9]/g, '').substring(0, 6);
      digits.split('').forEach((char, i) => {
        if (i < otpInputs.length) otpInputs[i].value = char;
      });
      const lastFilled = Math.min(digits.length, otpInputs.length) - 1;
      if (lastFilled >= 0) otpInputs[lastFilled].focus();
    });

    input.addEventListener('focus', () => {
      input.style.borderColor = 'var(--green)';
      input.style.boxShadow = '0 0 0 3px var(--green-ring)';
    });

    input.addEventListener('blur', () => {
      input.style.borderColor = '#cdd6d1';
      input.style.boxShadow = 'none';
    });
  });

  // Combine OTP fields before form submission
  function combineOtpFields(event) {
    const otpValue = Array.from(otpInputs).map(input => input.value).join('');
    document.getElementById('otp-combined').value = otpValue;
    if (otpValue.length !== 6) {
      event.preventDefault();
      alert('Please enter all 6 digits');
    }
  }

  // Resend Timer
  function startResendTimer() {
    let timeLeft = 59;
    const timerDisplay = document.getElementById('resend-timer');

    const interval = setInterval(() => {
      timeLeft--;
      const minutes = Math.floor(timeLeft / 60);
      const seconds = timeLeft % 60;
      timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

      if (timeLeft <= 0) {
        clearInterval(interval);
        timerDisplay.parentElement.innerHTML = '<a href="javascript:void(0)" onclick="resendOtp()" style="color: var(--green-mid); font-weight: 600; text-decoration: none;">Resend OTP</a>';
      }
    }, 1000);
  }

  // Resend OTP function
  function resendOtp() {
    alert('OTP Resent!');
    startResendTimer();
  }

  // Go to Join page
  function goToJoin() {
    window.location.href = '{{ route("join") }}';
  }

  // Start timer on page load
  startResendTimer();
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

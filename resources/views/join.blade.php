<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Join ISGH</title>
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
    .nav-pill .jn {
      background: var(--green-mid) !important;
      color: #fff !important;
      font-weight: 600 !important;
    }
    .nav-pill .jn:hover { background: var(--green) !important; }

    .mob-nav { display: none; gap: 0.5rem; }
    .mob-nav a {
      font-size: 0.8rem; font-weight: 600;
      padding: 0.38rem 1rem; border-radius: 999px; text-decoration: none;
    }
    .mob-nav .si { background: #1a2119; color: #dde8e2; }
    .mob-nav .jn { background: var(--green-mid); color: #fff; }

    .main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 2rem 2.5rem;
      /* margin-left: 46px;
      margin-right: 46px; */
      width: 100%;
      max-width: 100%;
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
      box-shadow: 0 4px 40px rgba(0, 0, 0, 0.25);
      
      padding: 2.8rem 2.8rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      border:10px solid rgba(247, 247, 247, 1)
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
      /* font-size: 2rem;
      font-weight: 700; */
      color: #111;
      margin-bottom: 0.3rem;
      line-height: 1.15;
      font-size: 48px;
      font-weight: 490;
    }
    .join-box p { font-size: 20px;
    color: #7a8a82;
    font-weight: 400;}

    .divider {
      border: none;
      border-top: 1px solid #dde5e0;
      margin: 0 0 1.5rem;
    }

    .form-title {
      font-size: 32px;
      font-weight: 590;
      color: #111;
      text-align: center;
      margin-bottom: 0.22rem;
    }
    .form-sub {
    font-size: 20px;
    color: #94a49b;
    text-align: center;
    margin-bottom: 1.8rem;
    font-weight: 400;
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
    .left-card .field label { background: transparent; top: -0.9rem; }
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
      margin-bottom:100px;
      border:5px solid #ffff;
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
    .mobile-join-visual {
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
      .main { padding: 1rem 0.85rem 1.6rem; align-items: flex-start; }
      .cards-wrapper { max-width: 100%; }
      .left-card {
        padding: 1rem 0.9rem 1.1rem;
        border-width: 8px;
        border-radius: 1.2rem;
      }
      .join-box {
        margin-bottom: 1rem;
        padding: 0.95rem 1rem 0.8rem;
      }
      .join-box h1 { font-size: 1.55rem; }
      .join-box p { font-size: 0.78rem; }
      .form-title { font-size: 1.05rem; margin-bottom: 0.2rem; }
      .form-sub { font-size: 0.78rem; margin-bottom: 1.05rem; }
      .divider { margin-bottom: 1rem; }
      .field { margin-bottom: 0.9rem; }
      .field label { font-size: 0.62rem; top: -0.42rem; }
      .field input { padding: 0.72rem 0.9rem; font-size: 0.88rem; border-radius: 0.55rem; }
      .btn-otp {
        padding: 0.82rem 1rem;
        font-size: 0.92rem;
        margin-bottom: 0.2rem;
        border-width: 4px;
      }
      .right-card { display: none; }
      .mobile-join-visual {
        display: block;
        margin-top: 0.25rem;
        margin-bottom: 1rem;
        border-radius: 0.95rem;
        overflow: hidden;
        border: 1px solid rgba(224,231,227,0.95);
        box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        background: linear-gradient(135deg, #0c7b52 0%, #0b5f40 100%);
        padding: 0.35rem;
      }
      .mobile-join-visual .join-visual-frame {
        position: relative;
        overflow: hidden;
        border-radius: 0.8rem;
        min-height: 245px;
        background: #0c7b52;
      }
      .mobile-join-visual img {
        width: 100%;
        height: 245px;
        object-fit: cover;
        object-position: center top;
        filter: grayscale(100%);
        display: block;
      }
    }
    @media (max-width: 380px) {
      .main { padding-left: 0.65rem; padding-right: 0.65rem; }
      .left-card { padding: 0.9rem 0.75rem 1rem; }
      .join-box h1 { font-size: 1.35rem; }
      .join-box p { font-size: 0.72rem; }
      .form-title { font-size: 0.98rem; }
      .form-sub { font-size: 0.72rem; }
      .mobile-join-visual img,
      .mobile-join-visual .join-visual-frame { height: 220px; min-height: 220px; }
    }
    .navbar-glass {
      background: rgba(10, 10, 10, 0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
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

  <main class="main">
    <div class="cards-wrapper">
      <div class="left-card">
        <div class="join-box">
          <h1>Join ISGH</h1>
          <p>Become part of our community</p>
        </div>

        <div class="mobile-join-visual">
          <div class="join-visual-frame">
            <img
              src="{{ asset('images/happy.png') }}"
              alt="Family together"
              loading="lazy"
            />
          </div>
        </div>

        <p class="form-title">Get Started</p>
        <p class="form-sub">Enter your contact details for 2FA verification</p>
        <hr class="divider"/>

        <form action="{{route('otp.send')}}" method="POST" id="joinForm">
          @csrf
          <div class="field" style="margin-top:1.4rem;">
            <label>Email <span>*</span></label>
            <input type="email" name="email" id="joinEmail" placeholder="ali44@gmail.com" required autocomplete="email" />
            @error('email')
              <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
            <div id="emailCheckMsg" style="font-size:0.78rem;margin-top:0.35rem;min-height:1rem;"></div>
          </div>

          <button type="submit" class="btn-otp" id="joinSubmitBtn">Send OTP</button>
        </form>

        <script>
          const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                          || '{{ csrf_token() }}';

          async function checkEmailExists(email) {
            try {
              const res  = await fetch('{{ route("membership.check-email") }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrfToken, 'Accept': 'application/json' },
                body:    JSON.stringify({ email }),
              });
              const data = await res.json();
              return data.exists === true;
            } catch (e) {
              return false; // don't block on network error
            }
          }

          const emailInput  = document.getElementById('joinEmail');
          const emailMsg    = document.getElementById('emailCheckMsg');
          const submitBtn   = document.getElementById('joinSubmitBtn');
          let _emailBlocked = false;

          emailInput.addEventListener('blur', async () => {
            const email = emailInput.value.trim();
            if (!email) return;
            emailMsg.textContent = 'Checking…';
            emailMsg.style.color = '#6b7280';
            const exists = await checkEmailExists(email);
            if (exists) {
              emailMsg.textContent = 'This email is already registered. Please use a different email or contact ISGH support.';
              emailMsg.style.color = '#dc2626';
              emailInput.style.borderColor = '#dc2626';
              _emailBlocked = true;
            } else {
              emailMsg.textContent = '✓ Email is available.';
              emailMsg.style.color = '#10b981';
              emailInput.style.borderColor = '';
              _emailBlocked = false;
            }
          });

          emailInput.addEventListener('input', () => {
            emailMsg.textContent = '';
            emailInput.style.borderColor = '';
            _emailBlocked = false;
          });

          document.getElementById('joinForm').addEventListener('submit', async (e) => {
            if (_emailBlocked) {
              e.preventDefault();
              emailMsg.textContent = 'This email is already registered. Please use a different email or contact ISGH support.';
              emailMsg.style.color = '#dc2626';
              return;
            }
            // If user skipped blur, run check on submit too
            const email = emailInput.value.trim();
            if (email) {
              e.preventDefault();
              submitBtn.disabled = true;
              submitBtn.textContent = 'Checking…';
              const exists = await checkEmailExists(email);
              if (exists) {
                emailMsg.textContent = 'This email is already registered. Please use a different email or contact ISGH support.';
                emailMsg.style.color = '#dc2626';
                emailInput.style.borderColor = '#dc2626';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send OTP';
              } else {
                e.target.submit();
              }
            }
          });
        </script>
      </div>

      <div class="right-card">
        <div class="ring r1"></div>
        <div class="ring r2"></div>
        <div class="ring r3"></div>
        <img
          src="{{ asset('images/happy.png') }}"
          alt="Family together"
          loading="lazy"
        />
      </div>
    </div>
  </main>
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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Join ISGH</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    body { background: #efefef; font-family: 'Segoe UI', sans-serif; }
 
    /* Hamburger animation */
    .ham-line { transition: all 0.3s ease; transform-origin: center; display: block; }
    #menu-toggle:checked ~ nav #ham .ham-line:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    #menu-toggle:checked ~ nav #ham .ham-line:nth-child(2) { opacity: 0; transform: scaleX(0); }
    #menu-toggle:checked ~ nav #ham .ham-line:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
 
    /* Mobile dropdown */
    #mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.4s ease, opacity 0.3s ease; opacity: 0; }
    #menu-toggle:checked ~ nav #mobile-menu { max-height: 500px; opacity: 1; }
 
    /* Hide checkbox */
    #menu-toggle { display: none; }
  </style>
</head>
<body class="min-h-screen flex flex-col">
    @include('layouts.navbar')
<div class="" style="border: 10px solid #ffff;border-radius: 40px; background: rgba(248, 248, 248, 1);">
  <main class="main">
    <div class="cards-wrapper">
      <div class="left-card">
        <div class="join-box">
          <h1>Join ISGH</h1>
          <p>Become part of our community</p>
        </div>

        <p class="form-title mt-10">Get Started</p>
        <p class="form-sub">Enter your contact details for 2FA verification</p>
        <hr class="divider"/>
        
        <form action="{{ route('membership.submit') }}" method="POST">
          @csrf
          <div class="field mt-10">
            <label>Email <span>*</span></label>
            <input type="email" name="email" placeholder="ali44@gmail.com" required />
            @error('email')
              <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
          </div>

          <div class="field">
            <label>Phone Number <span>*</span></label>
            <input type="tel" name="phone" placeholder="889-000-23928" required />
            @error('phone')
              <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
          </div>

          <button type="submit" class="btn-otp">Send OTP</button>
        </form>
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

</body>
</html>

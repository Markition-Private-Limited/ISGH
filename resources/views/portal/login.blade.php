<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Sign In — ISGH Staff Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(ellipse at 30% 40%, #1a4a3a 0%, #0d2d22 40%, #061a14 100%);
    }

    .card {
      background: #fff;
      border-radius: 20px;
      padding: 40px 36px 36px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 24px 64px rgba(0,0,0,.35);
    }

    /* ── Logo & heading ── */
    .card-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      margin-bottom: 24px;
    }
    .card-logo img {
      width: 60px;
      height: 60px;
      object-fit: contain;
    }
    .card-logo h1 {
      font-size: 1.25rem;
      font-weight: 700;
      color: #111;
      text-align: center;
      line-height: 1.3;
    }
    .card-logo .subtitle {
      font-size: .85rem;
      color: #4b5563;
      text-align: center;
    }
    .card-logo .tagline {
      font-size: .8rem;
      color: #6b7280;
      text-align: center;
      margin-top: 2px;
    }

    /* ── Error alert ── */
    .alert-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: .82rem;
      margin-bottom: 16px;
    }

    /* ── Form fields ── */
    .field {
      margin-bottom: 16px;
    }
    .field label {
      display: block;
      font-size: .8rem;
      font-weight: 500;
      color: #374151;
      margin-bottom: 6px;
    }
    .field label .req { color: #dc2626; }

    .input-wrap {
      position: relative;
    }
    .input-wrap input {
      width: 100%;
      height: 42px;
      border: 1.5px solid #d1d5db;
      border-radius: 8px;
      padding: 0 42px 0 12px;
      font-size: .875rem;
      color: #111;
      background: #fff;
      outline: none;
      transition: border-color .15s;
      font-family: inherit;
    }
    .input-wrap input::placeholder { color: #9ca3af; }
    .input-wrap input:focus { border-color: #1a5c3e; }

    .hint {
      font-size: .75rem;
      color: #9ca3af;
      margin-top: 4px;
    }

    /* eye toggle */
    .eye-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      color: #9ca3af;
      display: flex;
      align-items: center;
    }
    .eye-btn:hover { color: #6b7280; }
    .eye-btn .eye-closed { display: none; }

    /* ── Submit ── */
    .btn-submit {
      width: 100%;
      height: 46px;
      background: #1a5c3e;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: .95rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 8px;
      font-family: inherit;
      transition: background .15s;
    }
    .btn-submit:hover { background: #154d34; }
    .btn-submit:active { background: #0f3d28; }
  </style>
</head>
<body>

  <div class="card" role="main">

    {{-- Logo + branding --}}
    <div class="card-logo">
      <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" />
      <h1>Isgh Membership</h1>
      <p class="subtitle">( Elected Officials )</p>
      <p class="tagline">Login to access your admin dashboard</p>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
      <div class="alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('portal.login') }}" novalidate>
      @csrf

      {{-- Email --}}
      <div class="field">
        <label for="email">Email <span class="req">*</span></label>
        <div class="input-wrap">
          <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="ali44@gmail.com"
            autocomplete="username"
            required
            autofocus
          />
        </div>
        <p class="hint">Enter your registered email in ISGH's drive system</p>
      </div>

      {{-- Password --}}
      <div class="field">
        <label for="password">Password <span class="req">*</span></label>
        <div class="input-wrap">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••••••••••••••••••"
            autocomplete="current-password"
            required
          />
          <button type="button" class="eye-btn" id="eye-btn" aria-label="Toggle password visibility">
            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-submit">Sign In</button>
    </form>

  </div>

  <script>
    (function () {
      const btn   = document.getElementById('eye-btn');
      const input = document.getElementById('password');
      if (!btn || !input) return;
      btn.addEventListener('click', function () {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.querySelector('.eye-open').style.display  = isPassword ? 'none'  : '';
        btn.querySelector('.eye-closed').style.display = isPassword ? ''     : 'none';
        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      });
    })();
  </script>

</body>
</html>

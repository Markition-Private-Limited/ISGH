{{-- ============================================================
     Auth Layout â€” ISGH Staff Portal
     resources/views/layouts/auth.blade.php
     ============================================================ --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Login') â€” ISGH Staff Portal</title>

  {{-- Preconnect for Google Fonts --}}
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

  {{-- Portal Design System --}}
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />

  @stack('styles')
</head>
<body>

  {{-- Gradient auth background --}}
  <div class="auth-root" role="main">

    {{-- Flash messages --}}
    @if (session('error'))
      <div class="alert alert-danger" data-auto-hide="5000"
           style="position:fixed;top:1rem;right:1rem;max-width:340px;z-index:9999;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span>{{ session('error') }}</span>
      </div>
    @endif

    @if (session('success'))
      <div class="alert alert-success" data-auto-hide="4000"
           style="position:fixed;top:1rem;right:1rem;max-width:340px;z-index:9999;">
        {{ session('success') }}
      </div>
    @endif

    {{-- Page slot --}}
    @yield('content')

  </div>

  {{-- Portal JS --}}
  <script src="{{ asset('js/app.js') }}"></script>
  @stack('scripts')
</body>
</html>

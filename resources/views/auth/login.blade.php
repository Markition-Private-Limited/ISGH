{{-- ============================================================
     Login Page â€” ISGH Staff Portal
     resources/views/auth/login.blade.php
     ============================================================ --}}
@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')

<div class="auth-card" role="region" aria-label="Login form">

  {{-- Logo + Branding --}}
  <div class="auth-logo-wrap">
    <img
      src="{{ asset('images/logo.png') }}"
      alt="ISGH Logo"
      class="auth-logo"
    />
    <h1 class="auth-title">Isgh Membership</h1>
    <p class="auth-subtitle">( Elected Officials )</p>
    <p class="auth-subtitle" style="margin-top:6px;">Login to access your admin dashboard</p>
  </div>

  <hr class="auth-divider" />

  {{-- Validation Errors --}}
  @if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
           aria-hidden="true">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  {{-- Login Form --}}
  <form method="POST" action="{{ route('portal.login') }}" novalidate>
    @csrf

    {{-- Email --}}
    <div class="form-group">
      <label class="form-label" for="email">
        Email <span class="required" aria-hidden="true">*</span>
      </label>
      <input
        type="email"
        id="email"
        name="email"
        class="form-control @error('email') is-invalid @enderror"
        placeholder="ali44@gmail.com"
        value="{{ old('email') }}"
        autocomplete="username"
        required
        aria-required="true"
        aria-describedby="email-hint"
      />
      <p id="email-hint" class="form-hint">
        Enter your registered email in ISGH's drive system
      </p>
      @error('email')
        <p class="form-error" role="alert">{{ $message }}</p>
      @enderror
    </div>

    {{-- Password --}}
    <div class="form-group">
      <label class="form-label" for="password">
        Password <span class="required" aria-hidden="true">*</span>
      </label>
      <div class="input-group">
        <input
          type="password"
          id="password"
          name="password"
          class="form-control @error('password') is-invalid @enderror"
          placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
          autocomplete="current-password"
          required
          aria-required="true"
        />
        <button
          type="button"
          class="input-group-btn"
          data-password-toggle="password"
          aria-label="Show password"
        >
          {{-- Eye open icon --}}
          <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          {{-- Eye closed icon (hidden by default via JS) --}}
          <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
        </button>
      </div>
      @error('password')
        <p class="form-error" role="alert">{{ $message }}</p>
      @enderror
    </div>

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
      Sign In
    </button>

  </form>

</div>

@endsection

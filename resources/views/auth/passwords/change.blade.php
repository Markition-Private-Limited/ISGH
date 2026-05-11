@extends('layouts.auth')

@section('title', 'Change Password')

@section('content')

<div class="auth-card" role="region" aria-label="Change password form">

  {{-- Branding --}}
  <div class="auth-logo-wrap">
    <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="auth-logo" />
    <h1 class="auth-title">Change Password</h1>
    <p class="auth-subtitle">You must set a new password before continuing.</p>
  </div>

  <hr class="auth-divider" />

  {{-- Errors --}}
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

  <form method="POST" action="{{ route('portal.password.update') }}" novalidate>
    @csrf

    {{-- Current password --}}
    <div class="form-group">
      <label class="form-label" for="current_password">
        Current Password <span class="required" aria-hidden="true">*</span>
      </label>
      <div class="input-group">
        <input
          type="password"
          id="current_password"
          name="current_password"
          class="form-control @error('current_password') is-invalid @enderror"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        />
        <button type="button" class="input-group-btn" data-password-toggle="current_password" aria-label="Show password">
          <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
        </button>
      </div>
      @error('current_password')
        <p class="form-error" role="alert">{{ $message }}</p>
      @enderror
    </div>

    {{-- New password --}}
    <div class="form-group">
      <label class="form-label" for="password">
        New Password <span class="required" aria-hidden="true">*</span>
      </label>
      <div class="input-group">
        <input
          type="password"
          id="password"
          name="password"
          class="form-control @error('password') is-invalid @enderror"
          placeholder="••••••••"
          autocomplete="new-password"
          required
        />
        <button type="button" class="input-group-btn" data-password-toggle="password" aria-label="Show password">
          <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
        </button>
      </div>
      <p class="form-hint">Minimum 8 characters.</p>
      @error('password')
        <p class="form-error" role="alert">{{ $message }}</p>
      @enderror
    </div>

    {{-- Confirm new password --}}
    <div class="form-group">
      <label class="form-label" for="password_confirmation">
        Confirm New Password <span class="required" aria-hidden="true">*</span>
      </label>
      <div class="input-group">
        <input
          type="password"
          id="password_confirmation"
          name="password_confirmation"
          class="form-control"
          placeholder="••••••••"
          autocomplete="new-password"
          required
        />
        <button type="button" class="input-group-btn" data-password-toggle="password_confirmation" aria-label="Show password">
          <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
      Set New Password
    </button>

  </form>

  <div style="text-align:center; margin-top:1.25rem;">
    <form method="POST" action="{{ route('portal.logout') }}" style="display:inline;">
      @csrf
      <button type="submit" class="btn btn-ghost" style="font-size:0.85rem;">
        Sign out instead
      </button>
    </form>
  </div>

</div>

@endsection

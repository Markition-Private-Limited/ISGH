# Master OTP Bypass — Design Spec

**Date:** 2026-06-16  
**Status:** Approved

## Overview

Add a configurable master OTP to the member signup flow that can be used for testing purposes in any environment. When submitted, it bypasses the normal per-session OTP check and logs a warning.

## Configuration

Add `OTP_MASTER_CODE` to `.env` and `.env.example`.

- If the variable is absent or empty, the bypass is disabled — no accidental open door on fresh deployments.
- No default value is set in `.env.example` so developers must consciously opt in.

```
OTP_MASTER_CODE=
```

## Changes to `app/Http/Controllers/OtpController.php`

### OTP Generation (`sendOtp` and `resendOtp`)

After generating the random 6-digit OTP, check for a collision with the master code. If they match, regenerate. The OTP space is 1-in-1,000,000 so a single `do/while` retry is sufficient.

```php
$masterOtp = env('OTP_MASTER_CODE');
do {
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
} while ($masterOtp && $otp === $masterOtp);
```

Applies to both `sendOtp` and `resendOtp`.

### OTP Verification (`verifyOtp`)

After the expiry check and before the normal code comparison, check if the submitted OTP matches the master code. If it does, log a `Log::warning` and skip to the session-cleanup + redirect block. If it doesn't, fall through to the normal check.

```php
$masterOtp = env('OTP_MASTER_CODE');
if ($masterOtp && $request->otp === $masterOtp) {
    Log::warning("Master OTP used for email: " . Session::get('otp_email'));
    // fall through to session cleanup
} elseif ($request->otp !== Session::get('otp_code')) {
    return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
}
```

Session cleanup (forget `otp_code`, `otp_email`, `otp_expires_at`) and the redirect to `membership-types` remain unchanged.

## Logging

Uses `Log::warning` (not `Log::info`) so master OTP usage stands out in the log as an elevated event.

## Files Affected

| File | Change |
|------|--------|
| `.env` | Add `OTP_MASTER_CODE=` |
| `.env.example` | Add `OTP_MASTER_CODE=` |
| `app/Http/Controllers/OtpController.php` | Generation collision guard + verification bypass |

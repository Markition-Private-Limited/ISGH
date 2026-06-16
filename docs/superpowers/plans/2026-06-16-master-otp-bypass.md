# Master OTP Bypass Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an `OTP_MASTER_CODE` env var that, when set, always passes OTP verification in the member signup flow and never collides with a generated OTP.

**Architecture:** Single env var read in `OtpController` at generation time (collision guard) and at verification time (bypass check + warning log). No new classes or middleware — all changes are in one controller.

**Tech Stack:** Laravel 10+, PHPUnit feature tests, `.env` config.

---

## Files

| File | Action |
|------|--------|
| `.env` | Add `OTP_MASTER_CODE=` line |
| `.env.example` | Add `OTP_MASTER_CODE=` line |
| `app/Http/Controllers/OtpController.php` | Collision guard in `sendOtp`/`resendOtp`; bypass check in `verifyOtp` |
| `tests/Feature/OtpControllerTest.php` | New — covers master OTP bypass, collision guard, and normal flow |

---

## Task 1: Add env var to `.env` and `.env.example`

**Files:**
- Modify: `.env`
- Modify: `.env.example`

- [ ] **Step 1: Add to `.env.example`**

Open `.env.example`. After the `ADMIN_TOKEN=` line add:

```
OTP_MASTER_CODE=
```

- [ ] **Step 2: Add to `.env`**

Open `.env`. After the `ADMIN_TOKEN=` line add:

```
OTP_MASTER_CODE=
```

Leave the value blank for now — tests will set it via `config()` overrides.

- [ ] **Step 3: Commit**

```bash
git add .env.example
git commit -m "chore: add OTP_MASTER_CODE env var placeholder"
```

(Do NOT commit `.env` — it is gitignored.)

---

## Task 2: Write failing tests for master OTP bypass

**Files:**
- Create: `tests/Feature/OtpControllerTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OtpControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Verification: master OTP bypasses session code ────────────────────

    public function test_master_otp_bypasses_normal_code_check(): void
    {
        config(['app.otp_master_code' => '000000']);

        $this->withSession([
            'otp_code'       => '123456',
            'otp_email'      => 'test@example.com',
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $response = $this->post(route('otp.verify'), ['otp' => '000000']);

        $response->assertRedirect(route('membership-types'));
        $this->assertFalse(session()->has('otp_code'));
        $this->assertTrue(session()->has('verified_email'));
        $this->assertEquals('test@example.com', session('verified_email'));
    }

    public function test_master_otp_logs_warning(): void
    {
        config(['app.otp_master_code' => '000000']);
        Log::shouldReceive('warning')
            ->once()
            ->with('Master OTP used for email: test@example.com');

        $this->withSession([
            'otp_code'       => '123456',
            'otp_email'      => 'test@example.com',
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $this->post(route('otp.verify'), ['otp' => '000000']);
    }

    public function test_master_otp_clears_otp_session_keys(): void
    {
        config(['app.otp_master_code' => '000000']);

        $this->withSession([
            'otp_code'       => '123456',
            'otp_email'      => 'test@example.com',
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $this->post(route('otp.verify'), ['otp' => '000000']);

        $this->assertFalse(session()->has('otp_code'));
        $this->assertFalse(session()->has('otp_email'));
        $this->assertFalse(session()->has('otp_expires_at'));
    }

    public function test_master_otp_disabled_when_env_var_empty(): void
    {
        config(['app.otp_master_code' => '']);

        $this->withSession([
            'otp_code'       => '123456',
            'otp_email'      => 'test@example.com',
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        // Submitting '000000' should NOT pass when master code is empty
        $response = $this->post(route('otp.verify'), ['otp' => '000000']);
        $response->assertSessionHasErrors(['otp']);
    }

    public function test_wrong_otp_still_rejected_when_master_otp_set(): void
    {
        config(['app.otp_master_code' => '000000']);

        $this->withSession([
            'otp_code'       => '123456',
            'otp_email'      => 'test@example.com',
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $response = $this->post(route('otp.verify'), ['otp' => '999999']);
        $response->assertSessionHasErrors(['otp']);
    }
}
```

- [ ] **Step 2: Run the tests to confirm they fail**

```bash
php artisan test tests/Feature/OtpControllerTest.php
```

Expected: all 5 tests FAIL (method/config not wired yet).

---

## Task 3: Wire `OTP_MASTER_CODE` into Laravel config

**Files:**
- Modify: `config/app.php`

The controller should read from `config()` (cacheable) not `env()` directly (not cacheable after `php artisan config:cache`).

- [ ] **Step 1: Add config key to `config/app.php`**

Open `config/app.php`. Find the closing `];` at the end of the file and add before it:

```php
    'otp_master_code' => env('OTP_MASTER_CODE', ''),
```

- [ ] **Step 2: Verify config loads**

```bash
php artisan tinker --execute="echo config('app.otp_master_code');"
```

Expected: prints an empty string (since `.env` has `OTP_MASTER_CODE=`).

- [ ] **Step 3: Commit**

```bash
git add config/app.php
git commit -m "chore: expose OTP_MASTER_CODE via config/app.php"
```

---

## Task 4: Add collision guard to `sendOtp` and `resendOtp`

**Files:**
- Modify: `app/Http/Controllers/OtpController.php`

- [ ] **Step 1: Update `sendOtp` — replace OTP generation line**

In `OtpController::sendOtp`, find:

```php
        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
```

Replace with:

```php
        // Generate a 6-digit OTP, ensuring it never equals the master code
        $masterOtp = config('app.otp_master_code');
        do {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($masterOtp && $otp === $masterOtp);
```

- [ ] **Step 2: Update `resendOtp` — replace OTP generation line**

In `OtpController::resendOtp`, find:

```php
        $otp   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
```

Replace with:

```php
        $masterOtp = config('app.otp_master_code');
        do {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($masterOtp && $otp === $masterOtp);
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/OtpController.php
git commit -m "feat: guard OTP generation against collision with master code"
```

---

## Task 5: Add master OTP bypass to `verifyOtp`

**Files:**
- Modify: `app/Http/Controllers/OtpController.php`

- [ ] **Step 1: Update the OTP check block in `verifyOtp`**

Find this block (around line 85):

```php
        // Check the code itself
        if ($request->otp !== Session::get('otp_code')) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }
```

Replace with:

```php
        // Check the code itself — master OTP bypasses the session code
        $masterOtp = config('app.otp_master_code');
        if ($masterOtp && $request->otp === $masterOtp) {
            Log::warning('Master OTP used for email: ' . Session::get('otp_email'));
        } elseif ($request->otp !== Session::get('otp_code')) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }
```

- [ ] **Step 2: Confirm `Log` is imported at the top of the file**

Check that `use Illuminate\Support\Facades\Log;` exists in the imports. If not, add it after the last `use` statement.

- [ ] **Step 3: Run the tests**

```bash
php artisan test tests/Feature/OtpControllerTest.php
```

Expected: all 5 tests PASS.

- [ ] **Step 4: Run the full test suite to check for regressions**

```bash
php artisan test
```

Expected: no new failures.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/OtpController.php tests/Feature/OtpControllerTest.php
git commit -m "feat: add master OTP bypass with warning log for testing"
```

---

## Task 6: Manual smoke test

- [ ] **Step 1: Set master code in `.env`**

In `.env` set:
```
OTP_MASTER_CODE=000000
```

- [ ] **Step 2: Clear config cache**

```bash
php artisan config:clear
```

- [ ] **Step 3: Navigate to the signup flow**

Go to `http://localhost/join`, enter any email, submit. You will be redirected to the OTP entry page.

- [ ] **Step 4: Enter the master OTP**

Enter `000000` and submit. You should be redirected to the membership-types page without needing the emailed code.

- [ ] **Step 5: Confirm warning in log**

Open `storage/logs/laravel.log`. Confirm a line like:

```
[WARNING] Master OTP used for email: <your email>
```

- [ ] **Step 6: Reset `.env` master code to blank**

```
OTP_MASTER_CODE=
```

Then clear config again:

```bash
php artisan config:clear
```

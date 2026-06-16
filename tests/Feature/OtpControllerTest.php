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

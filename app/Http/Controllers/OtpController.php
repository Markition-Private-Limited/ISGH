<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class OtpController extends Controller
{
    // ─── Step 1: Show the "enter email" form ─────────────────────────────────
    public function showForm()
    {
        return view('otp.form');
    }

    // ─── Step 2: Generate OTP, store in session, send email ──────────────────
    public function sendOtp(Request $request)
    {
        try {
            $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in session (never touches the database)
        Session::put('otp_code',       $otp);
        Session::put('otp_email',      $request->email);
        Session::put('otp_expires_at', now()->addMinutes(10)->timestamp);

        // Send the OTP email
        Mail::to($request->email)->send(new OtpMail($otp, $request->email));
        return redirect()->route('otp.verify.form')
                         ->with('success', 'OTP sent! Please check your inbox.');
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    // ─── Step 3: Show the "enter OTP" form ───────────────────────────────────
    public function showVerifyForm()
    {
        // Guard: must have an active OTP session
        if (! Session::has('otp_code')) {
            return redirect()->route('join')
                             ->with('error', 'Please submit your email first.');
        }

        return view('verify-otp', [
            'email' => Session::get('otp_email'),
        ]);
    }

    // ─── Step 4: Validate the submitted OTP ──────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        // Check session existence
        if (! Session::has('otp_code')) {
            return redirect()->route('join')
                             ->with('error', 'Session expired. Please start again.');
        }

        // Check expiry
        if (now()->timestamp > Session::get('otp_expires_at')) {
            Session::forget(['otp_code', 'otp_email', 'otp_expires_at']);

            return redirect()->route('otp.form')
                             ->with('error', 'OTP has expired. Please request a new one.');
        }

        // Check the code itself
        if ($request->otp !== Session::get('otp_code')) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }

        // ✅ OTP is valid — clear from session so it cannot be reused
        $verifiedEmail = Session::get('otp_email');
        Session::forget(['otp_code', 'otp_email', 'otp_expires_at']);

        // Mark the email as verified in the session for the rest of the flow
        Session::put('verified_email',      $verifiedEmail);
        Session::put('otp_verified',        true);
        Session::put('otp_verified_email',  $verifiedEmail);

        return redirect()->route('membership-types');
    }

    // ─── Step 5: Success page ────────────────────────────────────────────────
    public function success()
    {
        if (! Session::has('verified_email')) {
            return redirect()->route('otp.form');
        }

        return view('otp.success', [
            'email' => Session::get('verified_email'),
        ]);
    }

    // ─── Resend OTP (reuses the same email already in session) ───────────────
    public function resendOtp()
    {
        if (! Session::has('otp_email')) {
            return redirect()->route('otp.form')
                             ->with('error', 'Please submit your email first.');
        }

        $email = Session::get('otp_email');
        $otp   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Session::put('otp_code',       $otp);
        Session::put('otp_expires_at', now()->addMinutes(10)->timestamp);

        Mail::to($email)->send(new OtpMail($otp, $email));

        return redirect()->route('otp.verify.form')
                         ->with('success', 'A new OTP has been sent to your email.');
    }
}
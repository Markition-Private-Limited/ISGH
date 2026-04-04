<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function join()
    {
        return view('join');
    }

    public function verifyOtp()
    {
        // Guard: must have an active OTP session (email was submitted)
        if (! Session::has('otp_email')) {
            return redirect()->route('join');
        }

        return view('verify-otp', [
            'email' => Session::get('otp_email'),
        ]);
    }

    public function membershipTypes()
    {
        // Guard: OTP must be verified before accessing the membership form
        if (! Session::get('otp_verified')) {
            return redirect()->route('join');
        }

        return view('membership-types', [
            'verifiedEmail' => session('otp_verified_email', ''),
        ]);
    }

    public function membershipVerification()
    {
        return view('membership-verification');
    }
}

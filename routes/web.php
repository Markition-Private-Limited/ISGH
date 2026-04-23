<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\OtpController;

Route::get("/", [HomeController::class, "index"])->name("home");
Route::get("/join", [HomeController::class, "join"])->name("join");
Route::get("/verify-otp", [HomeController::class, "verifyOtp"])->name("verify-otp");
Route::get("/membership-types", [HomeController::class, "membershipTypes"])->name("membership-types");
Route::get("/membership-verification", [HomeController::class, "membershipVerification"])->name("membership-verification");

// OTP routes
Route::controller(OtpController::class)->group(function () {
    Route::post('/otp/send',   'sendOtp'        )->name('otp.send');
    Route::get( '/otp/verify', 'showVerifyForm' )->name('otp.verify.form');
    Route::post('/otp/verify', 'verifyOtp'      )->name('otp.verify');
});

// Membership flow
Route::controller(MembershipController::class)->group(function () {
    Route::post('/membership/checkout',   'createCheckoutSession')->name('membership.checkout');
    Route::post('/membership/finalize-payment', 'finalizePayment')->name('membership.finalize-payment');
    Route::get( '/membership/success/{id}',    'success'             )->name('membership.success');
    Route::post('/membership/zip-lookup',   'zipLookup'           )->name('membership.zip-lookup');
    Route::post('/membership/verify',      'verifyMembership'    )->name('membership.verify');
    Route::post('/membership/check-email', 'checkEmail'          )->name('membership.check-email');
    Route::post('/membership/check-phone', 'checkPhone'          )->name('membership.check-phone');
});

// Stripe webhook — CSRF exempt (handled in bootstrap/app.php)
Route::post('/membership/webhook', [MembershipController::class, 'stripeWebhook'])->name('membership.webhook');

// Admin panel — protected by token middleware
Route::middleware('admin.token')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',                                         [AdminController::class, 'index']    )->name('dashboard');
    Route::get('/registrations/{registration}',            [AdminController::class, 'show']     )->name('show');
    Route::post('/registrations/{registration}/retry',     [AdminController::class, 'retry']    )->name('retry');
    Route::post('/registrations/retry-all',                [AdminController::class, 'retryAll'] )->name('retry-all');
    Route::post('/login',                                  [AdminController::class, 'login']    )->name('login');
});

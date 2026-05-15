<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/join', [HomeController::class, 'join'])->name('join');
Route::get('/verify-otp', [HomeController::class, 'verifyOtp'])->name('verify-otp');
Route::get('/membership-types', [HomeController::class, 'membershipTypes'])->name('membership-types');
Route::get('/membership-verification', [HomeController::class, 'membershipVerification'])->name('membership-verification');

// OTP routes
Route::controller(OtpController::class)->group(function () {
    Route::post('/otp/send', 'sendOtp')->name('otp.send');
    Route::get('/otp/verify', 'showVerifyForm')->name('otp.verify.form');
    Route::post('/otp/verify', 'verifyOtp')->name('otp.verify');
});

// Membership flow
Route::controller(MembershipController::class)->group(function () {
    Route::post('/membership/checkout', 'createCheckoutSession')->name('membership.checkout');
    Route::post('/membership/finalize-payment', 'finalizePayment')->name('membership.finalize-payment');
    Route::get('/membership/success/{id}', 'success')->name('membership.success');
    Route::post('/membership/zip-lookup', 'zipLookup')->name('membership.zip-lookup');
    Route::post('/membership/verify', 'verifyMembership')->name('membership.verify');
    Route::post('/membership/upload-photo', 'uploadMemberPhoto')->name('membership.upload-photo');
    Route::post('/membership/check-email', 'checkEmail')->name('membership.check-email');
    Route::post('/membership/check-phone', 'checkPhone')->name('membership.check-phone');
    Route::post('/membership/ocr-id', 'ocrId')->name('membership.ocr-id');
});

// Stripe webhook — CSRF exempt (handled in bootstrap/app.php)
Route::get('/login', [PortalController::class, 'showLogin'])->name('login');
Route::post('/membership/webhook', [MembershipController::class, 'stripeWebhook'])->name('membership.webhook');

// ── Staff Portal (Elected Officials) ─────────────────────────────────────────
Route::prefix('admin')->name('portal.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('portal.login');
    });
    Route::get('/login', [PortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalController::class, 'login'])->name('login');
    Route::post('/logout', [PortalController::class, 'logout'])->name('logout');

    // Protected portal pages
    Route::middleware(['auth', 'active.user'])->group(function () {
        Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/members', [PortalController::class, 'members'])->name('members');
        Route::get('/members/export/csv', [PortalController::class, 'exportCsv'])->name('members.export.csv');
        Route::get('/members/export/pdf', [PortalController::class, 'exportPdf'])->name('members.export.pdf');
    });
});

// ── Member Portal ─────────────────────────────────────────────────────────────
Route::prefix('member-portal')->name('member-portal.')->group(function () {
    Route::get('/', fn() => redirect()->route('member-portal.login'));
    Route::get('/login',      [MemberPortalController::class, 'showLogin'])->name('login');
    Route::post('/send-otp',  [MemberPortalController::class, 'sendOtp'])->name('send-otp');
    Route::post('/verify-otp',[MemberPortalController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/resend-otp',[MemberPortalController::class, 'resendOtp'])->name('resend-otp');
    Route::post('/logout',    [MemberPortalController::class, 'logout'])->name('logout');
    
    Route::middleware('member.portal.auth')->group(function () {
        Route::get('/dashboard', [MemberPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile',   [MemberPortalController::class, 'profile'])->name('profile');
        Route::post('/profile/update', [MemberPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/payments', [MemberPortalController::class, 'payments'])->name('payments');

        // Membership renewal
        Route::get('/renew/summary',          [MemberPortalController::class, 'renewSummary'])->name('renew.summary');
        Route::post('/renew',                 [MemberPortalController::class, 'processRenewal'])->name('renew');
        Route::get('/renew/status/{renewal}', [MemberPortalController::class, 'renewStatus'])->name('renew.status');

        // Static content pages
        Route::get('/records',            [MemberPortalController::class, 'records'])->name('records');
        Route::get('/newsletter',         [MemberPortalController::class, 'newsletter'])->name('newsletter');
        Route::get('/financial-report',   [MemberPortalController::class, 'financialReport'])->name('financial-report');
        Route::get('/updates',            [MemberPortalController::class, 'updates'])->name('updates');
        Route::get('/nominees-training',  [MemberPortalController::class, 'nomineesTraining'])->name('nominees-training');
    });
});

// // Admin panel — protected by token middleware
// Route::middleware('admin.token')->prefix('admin')->name('admin.')->group(function () {
//     Route::get('/',                                         [AdminController::class, 'index']    )->name('dashboard');
//     Route::get('/registrations/{registration}',            [AdminController::class, 'show']     )->name('show');
//     Route::post('/registrations/{registration}/retry',     [AdminController::class, 'retry']    )->name('retry');
//     Route::post('/registrations/retry-all',                [AdminController::class, 'retryAll'] )->name('retry-all');
//     Route::post('/login',                                  [AdminController::class, 'login']    )->name('login');
// });

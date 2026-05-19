<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\LevelChange;
use App\Models\Renewal;
use App\Services\LevelChangeService;
use App\Services\MemberPortalService;
use App\Services\RenewalService;
use App\Services\WildApricotService;
use App\Support\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MemberPortalController extends Controller
{
    // ── Show Login ────────────────────────────────────────────────────────

    public function showLogin(Request $request)
    {
        if ($request->session()->get('member_portal_authenticated')) {
            return redirect()->route('member-portal.dashboard');
        }

        return view('member-portal.login');
    }

    // ── Step 1: Verify email exists in WildApricot & send OTP ─────────────

    public function sendOtp(Request $request, WildApricotService $wa)
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = strtolower(trim($request->input('email')));

        // Look up the member in WildApricot
        $contact = $wa->findMemberByEmail($email);

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'No member account found with that email address.',
            ], 422);
        }

        // Generate a 6-digit OTP and store it in the session (valid for 1 minute)
        $otp       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinute()->timestamp;

        $request->session()->put('member_portal_otp', [
            'code'       => $otp,
            'email'      => $email,
            'expires_at' => $expiresAt,
            'contact_id' => $contact['Id'] ?? null,
        ]);

        // Send the OTP email

        if(app()->environment('local')) {
            Log::info("MemberPortal: OTP for {$email} is {$otp}");
            return response()->json([
                'success' => true,
                'masked_email' => $this->maskEmail($email),
                'debug_otp' => $otp, // Include OTP in response for local testing
            ]);
        }
        
        try {
            Mail::to($email)->send(new OtpMail($otp, $email));
        } catch (\Throwable $e) {
            Log::error('MemberPortal: OTP mail failed', ['email' => $email, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.',
            ], 500);
        }

        return response()->json([
            'success'   => true,
            'masked_email' => $this->maskEmail($email),
        ]);
    }

    // ── Step 2: Verify OTP & authenticate via bearer token ────────────────

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => ['required', 'digits:6']]);

        $stored = $request->session()->get('member_portal_otp');

        if (! $stored) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please request a new code.',
            ], 422);
        }

        if (now()->timestamp > $stored['expires_at']) {
            $request->session()->forget('member_portal_otp');
            return response()->json([
                'success' => false,
                'message' => 'Your OTP has expired, please resend the code.',
            ], 422);
        }

        if ($request->input('otp') !== $stored['code']) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect verification code. Please try again.',
            ], 422);
        }

        // OTP correct — generate a bearer token and store auth state in session
        $token = bin2hex(random_bytes(32));

        $request->session()->forget('member_portal_otp');
        $request->session()->put('member_portal_authenticated', true);
        $request->session()->put('member_portal_token', $token);
        $request->session()->put('member_portal_email', $stored['email']);
        $request->session()->put('member_portal_contact_id', $stored['contact_id']);
        $request->session()->regenerate();

        // Assemble + cache the member's full WildApricot bundle.
        if (! empty($stored['contact_id'])) {
            try {
                app(MemberPortalService::class)->assembleBundle((int) $stored['contact_id']);
            } catch (\Throwable $e) {
                Log::error('MemberPortal: bundle assembly failed after OTP', [
                    'contact_id' => $stored['contact_id'], 'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'  => true,
            'redirect' => route('member-portal.dashboard'),
        ]);
    }

    // ── Resend OTP ────────────────────────────────────────────────────────

    public function resendOtp(Request $request)
    {
        $stored = $request->session()->get('member_portal_otp');

        if (! $stored) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please start over.',
            ], 422);
        }

        $otp       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinute()->timestamp;

        $request->session()->put('member_portal_otp', array_merge($stored, [
            'code'       => $otp,
            'expires_at' => $expiresAt,
        ]));

        try {
            Mail::to($stored['email'])->send(new OtpMail($otp, $stored['email']));
        } catch (\Throwable $e) {
            Log::error('MemberPortal: OTP resend mail failed', ['email' => $stored['email'], 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend email. Please try again.',
            ], 500);
        }

        return response()->json(['success' => true]);
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    public function dashboard(Request $request, MemberPortalService $portal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        $email     = $request->session()->get('member_portal_email');

        if (! $contactId) {
            return redirect()->route('member-portal.login');
        }

        if ($request->boolean('refresh')) {
            $portal->invalidate((int) $contactId);
        }

        $bundle  = $portal->getBundle((int) $contactId);
        $profile = new MemberProfile($bundle);

        return view('member-portal.dashboard', compact('profile', 'email'));
    }

    public function profile(Request $request, MemberPortalService $portal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        $email     = $request->session()->get('member_portal_email');

        if (! $contactId) {
            return redirect()->route('member-portal.login');
        }

        if ($request->boolean('refresh')) {
            $portal->invalidate((int) $contactId);
        }

        $bundle  = $portal->getBundle((int) $contactId);
        $profile = new MemberProfile($bundle);

        return view('member-portal.profile', compact('profile', 'email'));
    }

    public function payments(Request $request, MemberPortalService $portal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        $email     = $request->session()->get('member_portal_email');

        if (! $contactId) {
            return redirect()->route('member-portal.login');
        }

        if ($request->boolean('refresh')) {
            $portal->invalidate((int) $contactId);
        }

        $bundle  = $portal->getBundle((int) $contactId);
        $profile = new MemberProfile($bundle);

        return view('member-portal.payments', compact('profile', 'email'));
    }

    // ── Invoice detail (modal) ────────────────────────────────────────────

    /**
     * JSON detail for a single invoice, shown in the payments-page modal.
     * Guards that the invoice belongs to the logged-in member, fetches it
     * from WildApricot, and enriches paid invoices with card details from
     * the local Renewal / LevelChange Stripe records.
     */
    public function invoiceDetail(Request $request, MemberPortalService $portal, WildApricotService $wa, int $invoiceId)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Please sign in again.'], 401);
        }

        $profile = new MemberProfile($portal->getBundle((int) $contactId));

        // Ownership guard — the invoice ID must be one of the member's own.
        $owned = collect($profile->invoices)->firstWhere('id', $invoiceId);
        if (! $owned) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $invoice = $wa->getInvoiceById($invoiceId);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Could not load invoice details.'], 502);
        }

        $isPaid = (bool) ($invoice['IsPaid'] ?? $owned['isPaid'] ?? false);
        $issueDate = $owned['date'] ?: '';

        $payment = null;
        if ($isPaid) {
            $stripe = LevelChange::where('wa_invoice_id', $invoiceId)->first()
                ?? Renewal::where('wa_invoice_id', $invoiceId)->first();

            $paymentDate = $stripe?->paid_at?->format('Y-m-d') ?: $issueDate;
            $method = 'Online Payment';
            if ($stripe && $stripe->card_last4) {
                $brand  = ucwords((string) ($stripe->card_brand ?: 'Card'));
                $method = "{$brand} (**** {$stripe->card_last4})";
            }

            $payment = [
                'invoiceDate' => $issueDate,
                'method'      => $method,
                'paymentDate' => $paymentDate,
            ];
        }

        return response()->json([
            'success' => true,
            'invoice' => [
                'number'         => (string) ($invoice['DocumentNumber'] ?? $owned['number']),
                'issueDate'      => $issueDate,
                'billingPeriod'  => $profile->billingPeriod($owned),
                'isPaid'         => $isPaid,
                'status'         => $isPaid ? 'Paid' : 'Unpaid',
                'amount'         => (float) ($invoice['Value'] ?? $owned['amount']),
                'currency'       => 'USD',
                'memberName'     => $profile->fullName ?: 'Member',
                'membershipType' => $profile->level ?: '—',
                'payment'        => $payment,
            ],
        ]);
    }

    // ── Static content pages ──────────────────────────────────────────────
    // These pages render hardcoded content; the bundle is loaded only so the
    // topbar can display the member's name.

    public function records(Request $request, MemberPortalService $portal)
    {
        return $this->staticPage($request, $portal, 'member-portal.isgh-records');
    }

    public function newsletter(Request $request, MemberPortalService $portal)
    {
        return $this->staticPage($request, $portal, 'member-portal.newsletter');
    }

    public function financialReport(Request $request, MemberPortalService $portal)
    {
        return $this->staticPage($request, $portal, 'member-portal.financial-report');
    }

    public function updates(Request $request, MemberPortalService $portal)
    {
        return $this->staticPage($request, $portal, 'member-portal.updates');
    }

    public function nomineesTraining(Request $request, MemberPortalService $portal)
    {
        return $this->staticPage($request, $portal, 'member-portal.nominees-training');
    }

    /**
     * Render a static member-portal page, loading the member bundle so the
     * view can show the member's name. Falls back gracefully if the bundle
     * cannot be assembled.
     */
    private function staticPage(Request $request, MemberPortalService $portal, string $view)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        $email     = $request->session()->get('member_portal_email');

        if (! $contactId) {
            return redirect()->route('member-portal.login');
        }

        $profile = null;

        try {
            $bundle  = $portal->getBundle((int) $contactId);
            $profile = new MemberProfile($bundle);
        } catch (\Throwable $e) {
            Log::error('MemberPortal: bundle load failed for static page', [
                'contact_id' => $contactId, 'view' => $view, 'error' => $e->getMessage(),
            ]);
        }

        return view($view, compact('profile', 'email'));
    }

    // ── Save profile edits back to WildApricot ────────────────────────────

    public function updateProfile(Request $request, MemberPortalService $portal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please sign in again.'], 401);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'street'     => ['nullable', 'string', 'max:255'],
            'city'       => ['nullable', 'string', 'max:100'],
            'state'      => ['nullable', 'string', 'max:100'],
            'zip'        => ['nullable', 'string', 'max:20'],
            'dob'        => ['nullable', 'date'],
            'tx_dl'      => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $portal->updateProfile((int) $contactId, $validated);
        } catch (\RuntimeException $e) {
            // Expected: WildApricot rejected the update (e.g. validation, duplicate).
            Log::error('MemberPortal: profile update rejected by WildApricot', [
                'contact_id' => $contactId, 'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not save changes. Please try again.',
            ], 422);
        } catch (\Throwable $e) {
            // Unexpected: a real bug — log loudly and surface a generic error.
            Log::error('MemberPortal: profile update failed unexpectedly', [
                'contact_id' => $contactId, 'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Changes saved successfully.',
        ]);
    }

    // ── Membership Renewal ────────────────────────────────────────────────

    /** Renewal modal data: fee, projected renewal date, family count. */
    public function renewSummary(Request $request, MemberPortalService $portal, RenewalService $renewal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $profile = new MemberProfile($portal->getBundle((int) $contactId));

        if ($renewal->isLifetimeLevel($profile)) {
            return response()->json(['renewable' => false, 'message' => 'Lifetime memberships do not require renewal.']);
        }

        $summary = $renewal->buildSummary($profile);

        return response()->json(array_merge(['renewable' => true], $summary));
    }

    /** Run the Stripe charge for a renewal. */
    public function processRenewal(Request $request, MemberPortalService $portal, RenewalService $renewal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please sign in again.'], 401);
        }

        $validated = $request->validate([
            'payment_method_id' => ['required', 'string'],
            'monthly_amount'    => ['nullable', 'numeric', 'min:1'],
        ]);

        $profile = new MemberProfile($portal->getBundle((int) $contactId));

        if ($renewal->isLifetimeLevel($profile)) {
            return response()->json(['success' => false, 'message' => 'Lifetime memberships are not renewable.'], 422);
        }

        try {
            $result = $renewal->charge(
                (int) $contactId,
                $profile,
                $validated['payment_method_id'],
                isset($validated['monthly_amount']) ? (float) $validated['monthly_amount'] : null
            );
        } catch (\Throwable $e) {
            Log::error('MemberPortal: renewal failed', ['contact_id' => $contactId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not process the renewal. Please try again.'], 422);
        }

        $status = ($result['success'] ?? false) ? 200 : 402;
        return response()->json($result, $status);
    }

    /** Finalize a renewal after a 3DS challenge — no new charge. */
    public function finalizeRenewal(Request $request, RenewalService $renewal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please sign in again.'], 401);
        }

        $validated = $request->validate([
            'renewal_id'        => ['required', 'integer'],
            'payment_intent_id' => ['required', 'string'],
        ]);

        // Ownership guard — the renewal must belong to this member.
        $row = Renewal::find($validated['renewal_id']);
        if (! $row || (int) $row->contact_id !== (int) $contactId) {
            return response()->json(['success' => false, 'message' => 'Renewal not found.'], 404);
        }

        $result = $renewal->finalize((int) $validated['renewal_id'], $validated['payment_intent_id']);
        $status = ($result['success'] ?? false) ? 200 : 402;
        return response()->json($result, $status);
    }

    /** Renewal status for the success screen. */
    public function renewStatus(Request $request, Renewal $renewal)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId || (int) $renewal->contact_id !== (int) $contactId) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success'       => true,
            'status'        => $renewal->status,
            'processed'     => $renewal->processed,
            'wa_invoice_id' => $renewal->wa_invoice_id,
        ]);
    }

    // ── Membership Level Change ───────────────────────────────────────────

    /** The membership levels the member may switch to. */
    public function changeLevelOptions(Request $request, MemberPortalService $portal, LevelChangeService $levelChange)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $profile = new MemberProfile($portal->getBundle((int) $contactId));

        $currentSlug  = \App\Support\MembershipTypes::slugFromLevelName($profile->level);
        $currentLabel = \App\Support\MembershipTypes::labelForSlug($currentSlug);

        return response()->json([
            'success' => true,
            'current' => [
                'type'     => $currentSlug,
                'label'    => $currentLabel !== '' ? $currentLabel : ($profile->level ?: 'Current Membership'),
                'feeLabel' => $profile->yearlyFee !== '' ? $profile->yearlyFee . ' (USD)' : '—',
            ],
            'levels'  => $levelChange->availableLevels($profile),
        ]);
    }

    /** Run the Stripe charge for a level change. */
    public function processLevelChange(Request $request, MemberPortalService $portal, LevelChangeService $levelChange)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please sign in again.'], 401);
        }

        $validated = $request->validate([
            'target_type'                 => ['required', 'string'],
            'payment_method_id'           => ['required', 'string'],
            'monthly_amount'              => ['nullable', 'numeric', 'min:1'],
            'family_members'              => ['nullable', 'array'],
            'family_members.*.first_name' => ['nullable', 'string', 'max:100'],
            'family_members.*.last_name'  => ['nullable', 'string', 'max:100'],
            'family_members.*.email'      => ['nullable', 'email'],
            'family_members.*.phone'      => ['nullable', 'string', 'max:30'],
            'family_members.*.dob'        => ['nullable', 'string', 'max:20'],
        ]);

        $profile = new MemberProfile($portal->getBundle((int) $contactId));

        try {
            $result = $levelChange->charge(
                (int) $contactId,
                $profile,
                $validated['target_type'],
                $validated['family_members'] ?? [],
                $validated['payment_method_id'],
                isset($validated['monthly_amount']) ? (float) $validated['monthly_amount'] : null
            );
        } catch (\Throwable $e) {
            Log::error('MemberPortal: level change failed', ['contact_id' => $contactId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not process the level change. Please try again.'], 422);
        }

        $status = ($result['success'] ?? false) ? 200 : 402;
        return response()->json($result, $status);
    }

    /** Finalize a level change after a 3DS challenge — no new charge. */
    public function finalizeLevelChange(Request $request, LevelChangeService $levelChange)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please sign in again.'], 401);
        }

        $validated = $request->validate([
            'level_change_id'   => ['required', 'integer'],
            'payment_intent_id' => ['required', 'string'],
        ]);

        $row = LevelChange::find($validated['level_change_id']);
        if (! $row || (int) $row->contact_id !== (int) $contactId) {
            return response()->json(['success' => false, 'message' => 'Level change not found.'], 404);
        }

        $result = $levelChange->finalize((int) $validated['level_change_id'], $validated['payment_intent_id']);
        $status = ($result['success'] ?? false) ? 200 : 402;
        return response()->json($result, $status);
    }

    /** Level-change status for the success screen. */
    public function levelChangeStatus(Request $request, LevelChange $levelChange)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId || (int) $levelChange->contact_id !== (int) $contactId) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success'       => true,
            'status'        => $levelChange->status,
            'processed'     => $levelChange->processed,
            'wa_invoice_id' => $levelChange->wa_invoice_id,
            'to_type'       => $levelChange->to_type,
        ]);
    }

    // ── Logout ────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        // Destroy the entire session — not just the auth keys — so no member
        // data survives, and assign a fresh session ID + CSRF token.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('member-portal.login');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, min(3, mb_strlen($local)));
        return $visible . str_repeat('*', max(0, mb_strlen($local) - 3)) . '@' . $domain;
    }
}

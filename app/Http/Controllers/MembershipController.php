<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Services\WildApricotService;
use App\Services\ZipCenterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Throwable;

class MembershipController extends Controller
{
    private const FEES = [
        'family'                 => ['cents' => 4000,   'label' => '$40.00'],
        'individual'             => ['cents' => 2500,   'label' => '$25.00'],
        'flat'                   => ['cents' => 2000,   'label' => '$20.00 / member'],
        'checkomatic_family'     => ['cents' => 1000,   'label' => '$10.00/mo'],
        'checkomatic_individual' => ['cents' => 1000,   'label' => '$10.00/mo'],
        'lifetime_family'        => ['cents' => 150000, 'label' => '$1,500.00'],
        'lifetime_individual'    => ['cents' => 100000, 'label' => '$1,000.00'],
    ];

    public function __construct(
        private WildApricotService $wa,
        private ZipCenterService   $zipCenter,
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MEMBERSHIP VERIFICATION  (AJAX — called from verification page)
    // ─────────────────────────────────────────────────────────────────────

    public function checkEmail(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['exists' => false]);
        }

        try {
            $contact = $this->wa->searchContact($email, '', '', '');
            return response()->json(['exists' => $contact !== null]);
        } catch (\Throwable $e) {
            Log::error('WA email check failed', ['error' => $e->getMessage()]);
            // On error, don't block the user — let OTP flow proceed
            return response()->json(['exists' => false]);
        }
    }

    public function verifyMembership(Request $request)
    {
        $firstName = trim((string) $request->input('first_name', ''));
        $lastName  = trim((string) $request->input('last_name', ''));
        $email     = strtolower(trim((string) $request->input('email', '')));
        $phone     = trim((string) $request->input('phone', ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '') {
            return response()->json([
                'success' => false,
                'message' => 'First name, last name, email, and phone are all required.',
            ], 422);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ], 422);
        }

        try {
            $contact = $this->wa->searchContact($email, $firstName, $lastName, $phone);
        } catch (\Throwable $e) {
            Log::error('WA membership verification failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to the membership system. Please try again.',
            ], 500);
        }

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'No membership record found matching your details. Please check the information entered or contact ISGH support.',
            ]);
        }

        // Extract dynamic field values from WA response
        $get = fn(string $name) => $this->wa->extractFieldValue($contact, $name);

        Log::debug('WA contact keys for verification', [
            'keys'        => array_keys($contact),
            'MemberSince' => $contact['MemberSince'] ?? 'NOT PRESENT',
            'RenewalDue'  => $contact['RenewalDue']  ?? 'NOT PRESENT',
            'Status'      => $contact['Status']       ?? 'NOT PRESENT',
        ]);

        $status     = $contact['Status'] ?? 'Unknown';
        $levelName  = $contact['MembershipLevel']['Name'] ?? '—';
        $renewalRaw = $contact['RenewalDue']   ?? null;
        $sinceRaw   = $contact['MemberSince']  ?? null;

        $expiry = $renewalRaw ? \Carbon\Carbon::parse($renewalRaw)->format('M d, Y') : '—';
        $since  = $sinceRaw  ? \Carbon\Carbon::parse($sinceRaw)->format('M d, Y')   : '—';

        // Voting eligibility: Active status only
        $isActive = strtolower($status) === 'active';

        return response()->json([
            'success' => true,
            'member'  => [
                'name'    => trim(($contact['FirstName'] ?? '') . ' ' . ($contact['LastName'] ?? '')),
                'email'   => $contact['Email'] ?? $get('Email'),
                'phone'   => $get('Phone') ?: ($contact['Phone'] ?? ''),
                'type'    => $levelName,
                'status'  => $status,
                'since'   => $since,
                'expiry'  => $expiry,
                'zone'    => $get('Zone / Center') ?: $get('custom-9967573'),
                'voting'  => $isActive ? 'Yes' : 'No',
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ZIP LOOKUP  (AJAX — called on ZIP blur)
    // ─────────────────────────────────────────────────────────────────────

    public function zipLookup(Request $request)
    {
        $zip     = trim((string) $request->input('zip', ''));
        $centers = $this->zipCenter->lookup($zip);

        if (empty($centers)) {
            return response()->json([
                'success' => false,
                'message' => 'ISGH community services are not available in your area.',
            ]);
        }

        return response()->json([
            'success' => true,
            'centers' => $centers,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CREATE STRIPE CHECKOUT SESSION  (AJAX)
    //
    //  - Validates the form
    //  - Saves all form data to pending_registrations
    //  - Creates a Stripe Checkout Session
    //  - Returns the hosted checkout URL for the browser to redirect to
    // ─────────────────────────────────────────────────────────────────────

    public function createCheckoutSession(Request $request)
    {
        // Guard: OTP must be verified
        $verifiedEmail  = strtolower(trim((string) Session::get('otp_verified_email', '')));
        $submittedEmail = strtolower(trim((string) $request->input('primary.email', '')));

        if (! Session::get('otp_verified') || $verifiedEmail !== $submittedEmail) {
            return response()->json([
                'success' => false,
                'message' => ! Session::get('otp_verified')
                    ? 'Please verify your email address with OTP before paying.'
                    : 'The email you entered does not match your verified email (' . Session::get('otp_verified_email') . '). Please use the same email.',
            ], 422);
        }
        // dd($request->all());
        // Validate
        $validator = Validator::make($request->all(), [
            'membership_type'        => 'required|string|in:family,individual,flat,checkomatic_family,checkomatic_individual,lifetime_family,lifetime_individual',
            'primary.first_name'     => 'required|string|max:100',
            'primary.last_name'      => 'required|string|max:100',
            'primary.email'          => 'required|email',
            'primary.dob'            => ['required', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}$/', function($attr, $val, $fail) {
                [$m, $d, $y] = explode('/', $val);
                if (! checkdate((int)$m, (int)$d, (int)$y)) $fail('Primary date of birth is not a valid date.');
            }],
            'primary.tx_dl'          => 'required|string',
            'primary.street'         => 'required|string',
            'primary.city'           => 'required|string',
            'primary.zip'            => 'required|string',
            'primary.state'          => 'required|string',
            'primary.phone'          => 'required|string',
            'flat_members.*.dob'     => ['nullable', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}$/', function($attr, $val, $fail) {
                if (! $val) return;
                [$m, $d, $y] = explode('/', $val);
                if (! checkdate((int)$m, (int)$d, (int)$y)) $fail('A flat member date of birth is not a valid date.');
            }],
            'terms.agree'            => 'required|accepted',
            'terms.responsibility'   => 'required|accepted',
            'terms.privacy'          => 'required|accepted',
            'terms.communications'   => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $type = $request->input('membership_type');
        $fee  = self::FEES[$type];

        // Flat membership: $20 per member
        if ($type === 'flat') {
            // 1 primary member + additional flat family members
            $memberCount = 1 + count($request->input('flat_members', []));
            $totalCents  = $memberCount * 2000;
            $fee = [
                'cents' => $totalCents,
                'label' => '$' . number_format($totalCents / 100, 2),
            ];
        }

        // Stable internal reference used to retrieve this pending reg after Stripe redirects back
        $ref = Str::uuid()->toString();

        try {
            // Save form data now, keyed by our internal ref
            PendingRegistration::create([
                'stripe_intent_id' => $ref,
                'data'             => [
                    'type'         => $type,
                    'primary'      => $request->input('primary'),
                    'spouses'      => $request->input('spouses', []),
                    'flat_members' => $request->input('flat_members', []),
                    'zone'         => $request->input('zone', ''),
                    'amount_cents' => $fee['cents'],
                    'amount_label' => $fee['label'],
                ],
                'processed' => false,
            ]);

            $productName = ucwords(str_replace('_', ' ', $type)) . ' Membership — ISGH';

            // Create Stripe Checkout Session
            $session = CheckoutSession::create([
                'mode'                => 'payment',
                'customer_email'      => $request->input('primary.email'),
                'client_reference_id' => $ref,
                'line_items'          => [[
                    'price_data' => [
                        'currency'     => 'usd',
                        'unit_amount'  => $fee['cents'],
                        'product_data' => [
                            'name'        => $productName,
                            'description' => 'Islamic Society of Greater Houston',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => route('membership.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('membership-types') . '?cancelled=1',
                'metadata'    => [
                    'membership_type' => $type,
                    'member_name'     => $request->input('primary.first_name') . ' ' . $request->input('primary.last_name'),
                    'pending_ref'     => $ref,
                ],
            ]);

            Log::info('Stripe Checkout Session created', [
                'session_id' => $session->id,
                'ref'        => $ref,
                'type'       => $type,
            ]);

            return response()->json([
                'success'      => true,
                'checkout_url' => $session->url,
            ]);

        } catch (Throwable $e) {
            Log::error('Checkout Session create failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Payment setup failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SUCCESS PAGE  (GET — Stripe redirects here after payment)
    //
    //  Stripe appends ?session_id=cs_xxx to the success_url.
    //  We retrieve the session, verify it's paid, look up the pending
    //  registration via client_reference_id, process Wild Apricot once,
    //  then render the confirmation page.
    // ─────────────────────────────────────────────────────────────────────

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('home');
        }

        try {
            $session = CheckoutSession::retrieve($sessionId);
        } catch (Throwable $e) {
            Log::error('Could not retrieve Checkout Session', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return redirect()->route('home')->with('error', 'Could not verify your payment. Please contact support.');
        }

        if ($session->payment_status !== 'paid') {
            return redirect()->route('membership-types')->with('error', 'Payment was not completed. Please try again.');
        }

        $ref     = $session->client_reference_id;
        $pending = PendingRegistration::where('stripe_intent_id', $ref)->first();

        if (! $pending) {
            Log::error('No pending registration found for ref', ['ref' => $ref, 'session_id' => $sessionId]);
            return redirect()->route('home')->with('error', 'Registration not found. Please contact support.');
        }

        // Process Wild Apricot only once (browser may load this page multiple times)
        if (! $pending->processed) {
            $chargeId   = $session->payment_intent ?? $sessionId;
            $amountPaid = $session->amount_total / 100;
            $this->processWildApricot($pending, $chargeId, $amountPaid);
        }

        // Reload to get updated wa_contact_id
        $pending->refresh();

        return view('membership.success', [
            'member_name'     => $pending->data['primary']['first_name'] . ' ' . $pending->data['primary']['last_name'],
            'member_email'    => $pending->data['primary']['email'],
            'membership_type' => $pending->data['type'],
            'amount_label'    => $pending->data['amount_label'],
            'wa_contact_id'   => $pending->wa_contact_id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  STRIPE WEBHOOK  (FALLBACK)
    //
    //  Handles checkout.session.completed for cases where the browser
    //  never loaded the success URL (tab closed, network dropout, etc.).
    //  The processed flag prevents double-processing.
    // ─────────────────────────────────────────────────────────────────────

    public function stripeWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature invalid');
            return response('Invalid signature', 400);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response('OK', 200);
        }

        $session = $event->data->object;

        if ($session->payment_status !== 'paid') {
            return response('OK', 200);
        }

        $ref     = $session->client_reference_id;
        $pending = PendingRegistration::where('stripe_intent_id', $ref)->first();

        if (! $pending) {
            Log::warning('Webhook: no pending registration found', ['ref' => $ref]);
            return response('OK', 200);
        }

        if ($pending->processed) {
            Log::info('Webhook: already processed by success page', ['ref' => $ref]);
            return response('OK', 200);
        }

        Log::info('Webhook fallback: processing unhandled checkout', ['ref' => $ref]);

        $this->processWildApricot(
            $pending,
            $session->payment_intent ?? $session->id,
            $session->amount_total / 100
        );

        return response('OK', 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SHARED: CREATE EVERYTHING IN WILD APRICOT
    // ─────────────────────────────────────────────────────────────────────

    /** Public entry-point for admin retry — delegates to the same pipeline. */
    public function retryWildApricot(PendingRegistration $pending, string $chargeId, float $amountPaid): array
    {
        return $this->processWildApricot($pending, $chargeId, $amountPaid);
    }

    private function processWildApricot(PendingRegistration $pending, string $chargeId, float $amountPaid): array
    {
        $data = $pending->data;
        $type = $data['type'];

        $isFamilyType = in_array($type, ['family', 'checkomatic_family', 'lifetime_family']);
        $primaryRole  = match(true) {
            $type === 'flat'  => 'Owner',
            $isFamilyType     => 'Head of Household',
            default           => 'Individual',
        };

        // Mark stripe_paid so admin knows money was collected even if WA fails
        $pending->update(['stripe_paid' => true]);

        // Helper: record a failed step and re-throw
        $fail = function (string $step, Throwable $e) use ($pending): never {
            $pending->update([
                'wa_step'     => $step,
                'wa_error'    => $e->getMessage(),
                'wa_error_at' => now(),
                'retry_count' => $pending->retry_count + 1,
            ]);
            Log::error("processWildApricot failed at step [{$step}]", [
                'error' => $e->getMessage(),
                'ref'   => $pending->stripe_intent_id,
            ]);
            throw $e;
        };

        try {
            // ── Resume state: skip steps already completed on a previous attempt ──
            $contactId   = (int) ($pending->wa_contact_id ?? 0);
            $invoiceId   = (int) ($pending->wa_invoice_id ?? 0);
            $paymentDone = in_array($pending->wa_step, ['spouses', 'done']);

            // ── Step 1: Create Active WA contact (skip if contact already created) ──
            // bundleId / levelId — needed to link spouses as proper bundle members
            $bundleId = (int) ($data['wa_bundle_id'] ?? 0);
            $levelId  = (int) ($data['wa_level_id']  ?? 0);

            $termsAgreedAt = now()->toIso8601String();

            if (! $contactId) {
                $pending->update(['wa_step' => 'contact']);
                $primaryData = array_merge($data['primary'], [
                    'membership_type' => $type,
                    'role'            => $primaryRole,
                    'zone'            => $data['zone'] ?? '',
                    'terms_agreed_at' => $termsAgreedAt,
                ]);
                try {
                    $contact   = $this->wa->createActiveMember($primaryData);
                    $contactId = (int) $contact['Id'];
                    // Extract BundleId and LevelId — needed to link spouses as bundle members
                    $bundleId  = (int) $this->wa->extractFieldValue($contact, 'BundleId');
                    $levelId   = (int) ($contact['MembershipLevel']['Id'] ?? $this->wa->resolveLevelId($type));
                    // Persist so retry can skip this step and still have both IDs
                    $newData = array_merge($data, ['wa_bundle_id' => $bundleId, 'wa_level_id' => $levelId]);
                    $pending->update(['wa_contact_id' => $contactId, 'data' => $newData]);
                    $data = $newData;
                } catch (Throwable $e) { $fail('contact', $e); }
            } else {
                Log::info('processWildApricot: skipping contact step (already created)', [
                    'contact_id' => $contactId, 'bundle_id' => $bundleId, 'ref' => $pending->stripe_intent_id,
                ]);
            }

            // ── Step 2: Create WA invoice (skip if already created) ─────────
            if (! $invoiceId) {
                $pending->update(['wa_step' => 'invoice']);
                try {
                    $invoice   = $this->wa->createMembershipInvoice($contactId, $amountPaid, $type);
                    $invoiceId = (int) $invoice['Id'];
                    $pending->update(['wa_invoice_id' => $invoiceId]);
                } catch (Throwable $e) { $fail('invoice', $e); }
            } else {
                Log::info('processWildApricot: skipping invoice step (already created)', [
                    'invoice_id' => $invoiceId, 'ref' => $pending->stripe_intent_id,
                ]);
            }

            // ── Step 3: Record Stripe payment (skip if already recorded) ──────
            if (! $paymentDone) {
                $pending->update(['wa_step' => 'payment']);
                try {
                    $this->wa->recordPayment($contactId, $invoiceId, $amountPaid, $chargeId);
                } catch (Throwable $e) { $fail('payment', $e); }
            } else {
                Log::info('processWildApricot: skipping payment step (already recorded)', [
                    'ref' => $pending->stripe_intent_id,
                ]);
            }

            // ── Step 4: Add spouses / flat members ───────────────────────────
            $pending->update(['wa_step' => 'spouses']);
            if ($isFamilyType) {
                foreach ($data['spouses'] ?? [] as $spouse) {
                    if (empty($spouse['first_name'])) continue;
                    try {
                        $this->wa->addRelatedContact($contactId, $bundleId, $levelId, array_merge($spouse, [
                            'role'           => 'Spouse',
                            'zone'           => $data['zone'] ?? '',
                            'terms_agreed_at' => $termsAgreedAt,
                            'invoice_number' => $invoiceId,
                        ]));
                    } catch (Throwable $e) {
                        Log::error('WA spouse add failed', ['error' => $e->getMessage()]);
                    }
                }
            }
            if ($type === 'flat') {
                foreach ($data['flat_members'] ?? [] as $member) {
                    if (empty($member['first_name'])) continue;
                    try {
                        $this->wa->addRelatedContact($contactId, $bundleId, $levelId, [
                            'first_name'        => $member['first_name']  ?? '',
                            'last_name'         => $member['last_name']   ?? '',
                            'middle_name'       => $member['middle_name'] ?? '',
                            'email'             => $member['email']       ?? '',
                            'dob'               => $member['dob']         ?? '',
                            'phone'             => $member['phone']       ?? '',
                            'tx_dl'             => $member['tx_dl']       ?? '',
                            'role'              => $member['relation']    ?? 'Family Member',
                            'zone'              => $data['zone']          ?? '',
                            'street'            => $member['street']      ?: ($data['primary']['street'] ?? ''),
                            'city'              => $member['city']        ?: ($data['primary']['city']   ?? ''),
                            'state'             => $member['state']       ?: ($data['primary']['state']  ?? ''),
                            'zip'               => $member['zip']         ?: ($data['primary']['zip']    ?? ''),
                            'terms_agreed_at'   => $termsAgreedAt,
                            'member_identifier' => $contactId,
                            'invoice_number'    => $invoiceId,
                        ]);
                    } catch (Throwable $e) {
                        Log::error('WA flat member add failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            // ── Step 5: Mark fully processed ────────────────────────────────
            $pending->update([
                'processed'     => true,
                'wa_step'       => 'done',
                'wa_error'      => null,
                'wa_error_at'   => null,
                'wa_contact_id' => $contactId,
                'wa_invoice_id' => $invoiceId,
                'processed_at'  => now(),
            ]);

            Log::info('WA membership fully created', [
                'contact_id' => $contactId,
                'invoice_id' => $invoiceId,
                'type'       => $type,
                'charge_id'  => $chargeId,
            ]);

            return ['success' => true];

        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

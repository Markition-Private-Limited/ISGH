<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Services\WildApricotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminController extends Controller
{
    public function __construct(private WildApricotService $wa) {}

    // ── Login ─────────────────────────────────────────────────────────────

    public function login(\Illuminate\Http\Request $request)
    {
        $token = config('services.admin.token');
        if ($request->input('admin_token') === $token) {
            session(['admin_authenticated' => true]);
            return redirect()->route('admin.dashboard');
        }
        return back()->withErrors(['admin_token' => 'Invalid token.']);
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'failed'); // failed | all | done

        $query = PendingRegistration::orderByDesc('created_at');

        if ($filter === 'failed') {
            $query->where('stripe_paid', true)->where('processed', false);
        } elseif ($filter === 'done') {
            $query->where('processed', true);
        }
        // 'all' returns everything

        $registrations = $query->paginate(30)->withQueryString();

        $counts = [
            'failed'  => PendingRegistration::where('stripe_paid', true)->where('processed', false)->count(),
            'done'    => PendingRegistration::where('processed', true)->count(),
            'all'     => PendingRegistration::count(),
        ];

        return view('admin.dashboard', compact('registrations', 'filter', 'counts'));
    }

    // ── Detail view ───────────────────────────────────────────────────────

    public function show(PendingRegistration $registration)
    {
        return view('admin.show', compact('registration'));
    }

    // ── Retry single registration ─────────────────────────────────────────

    public function retry(PendingRegistration $registration)
    {
        if ($registration->processed) {
            return back()->with('info', 'Registration is already processed — nothing to retry.');
        }

        if (! $registration->stripe_paid) {
            return back()->with('error', 'Stripe payment not confirmed for this registration.');
        }

        // Retrieve Stripe session to get payment intent + amount
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $session    = \Stripe\Checkout\Session::retrieve($registration->stripe_intent_id);
            $chargeId   = $session->payment_intent ?? $registration->stripe_intent_id;
            $amountPaid = $session->amount_total / 100;
        } catch (Throwable $e) {
            // Fallback: use stored amount if Stripe lookup fails
            Log::warning('Admin retry: Stripe session lookup failed', ['error' => $e->getMessage()]);
            $chargeId   = $registration->stripe_intent_id;
            $amountPaid = ($registration->data['amount_cents'] ?? 0) / 100;
        }

        $mc     = new MembershipController($this->wa, app(\App\Services\ZipCenterService::class));
        $result = $mc->retryWildApricot($registration, $chargeId, $amountPaid);

        if ($result['success']) {
            return back()->with('success', 'Registration successfully processed in Wild Apricot.');
        }

        return back()->with('error', 'Retry failed: ' . $result['message']);
    }

    // ── Retry all failed ──────────────────────────────────────────────────

    public function retryAll()
    {
        $failed = PendingRegistration::where('stripe_paid', true)
            ->where('processed', false)
            ->get();

        $ok = 0;
        $fail = 0;

        foreach ($failed as $reg) {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $session    = \Stripe\Checkout\Session::retrieve($reg->stripe_intent_id);
                $chargeId   = $session->payment_intent ?? $reg->stripe_intent_id;
                $amountPaid = $session->amount_total / 100;
            } catch (Throwable $e) {
                $chargeId   = $reg->stripe_intent_id;
                $amountPaid = ($reg->data['amount_cents'] ?? 0) / 100;
            }

            $mc     = new MembershipController($this->wa, app(\App\Services\ZipCenterService::class));
            $result = $mc->retryWildApricot($reg, $chargeId, $amountPaid);

            $result['success'] ? $ok++ : $fail++;
        }

        return back()->with('success', "Retry complete — {$ok} succeeded, {$fail} failed.");
    }
}

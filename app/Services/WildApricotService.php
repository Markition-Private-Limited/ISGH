<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wild Apricot Service — used AFTER Stripe confirms payment
 *
 * Responsibilities:
 *  1. createActiveMember()   — POST /contacts  Status=Active
 *  2. createMemberInvoice()  — POST /invoices  for the membership fee
 *  3. recordPayment()        — POST /payments  marks invoice paid (Stripe ref)
 *  4. addRelatedContact()    — POST /contacts  spouse / family member
 */
class WildApricotService
{
    private string  $apiKey;
    private ?string $configuredAccountId;
    private string  $baseUrl = 'https://api.wildapricot.org/v2.3';
    private string  $authUrl = 'https://oauth.wildapricot.org/auth/token';

    public function __construct()
    {
        $this->apiKey              = config('services.wild_apricot.api_key',    env('WILD_APRICOT_API_KEY', ''));
        $this->configuredAccountId = config('services.wild_apricot.account_id', env('WILD_APRICOT_ACCOUNT_ID'));
    }

    // ─── AUTH ────────────────────────────────────────────────────────────────

    public function getAccessToken(): string
    {
        return Cache::remember('wa_access_token', 1500, function () {
            $r = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode('APIKEY:' . $this->apiKey),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ])->asForm()->post($this->authUrl, ['grant_type' => 'client_credentials', 'scope' => 'auto']);

            if (! $r->successful()) {
                throw new \RuntimeException('WA auth failed: ' . $r->body());
            }
            return $r->json('access_token');
        });
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getAccountId(): string
    {
        if ($this->configuredAccountId) return $this->configuredAccountId;

        return Cache::remember('wa_account_id', 86400, function () {
            $accounts = $this->apiGet('/accounts')->json();
            if (empty($accounts)) throw new \RuntimeException('No WA accounts found.');
            return (string) $accounts[0]['Id'];
        });
    }

    // ─── DROPDOWN CHOICES (Zone / Center  &  Role) ───────────────────────────
    // WA dropdown fields must be sent as {"Id": choiceId} not a plain label string.

    /**
     * Returns a map of [label => choiceId] for the Zone / Center dropdown.
     * Cached for 24 h (choices rarely change).
     */
    public function getZoneChoices(): array
    {
        return $this->getChoicesForField('wa_zone_choices', 'custom-9967573');
    }

    /**
     * Returns a map of [label => choiceId] for the Role dropdown.
     * Cached for 24 h.
     */
    public function getRoleChoices(): array
    {
        return $this->getChoicesForField('wa_role_choices', 'custom-16727578');
    }

    /**
     * Generic helper: fetch AllowedValues for a contact field by SystemCode
     * and return [label => id] map, cached under $cacheKey for 24 h.
     */
    private function getChoicesForField(string $cacheKey, string $systemCode): array
    {
        return Cache::remember($cacheKey, 86400, function () use ($systemCode) {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/contactfields");
            if (! $r->successful()) return [];

            foreach ((array) $r->json() as $field) {
                if (($field['SystemCode'] ?? '') === $systemCode) {
                    $map = [];
                    foreach ($field['AllowedValues'] ?? [] as $v) {
                        $map[$v['Label']] = (int) $v['Id'];
                    }
                    return $map;
                }
            }
            return [];
        });
    }

    /**
     * Looks up the SystemCode for a contact field by its FieldName.
     * Cached for 24 h. Returns null if not found.
     */
    private function getFieldSystemCode(string $fieldName): ?string
    {
        return Cache::remember('wa_field_code_' . md5($fieldName), 86400, function () use ($fieldName) {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/contactfields");
            if (! $r->successful()) return null;

            foreach ((array) $r->json() as $field) {
                if (strcasecmp($field['FieldName'] ?? '', $fieldName) === 0) {
                    Log::debug('WA field system code discovered', [
                        'field_name'  => $fieldName,
                        'system_code' => $field['SystemCode'],
                    ]);
                    return $field['SystemCode'];
                }
            }
            Log::warning('WA field not found by name', ['field_name' => $fieldName]);
            return null;
        });
    }

    // ─── MEMBERSHIP LEVELS ───────────────────────────────────────────────────

    public function getMembershipLevels(): array
    {
        // Serve a fresh cached copy when present (24h).
        $cached = Cache::get('wa_levels');
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        // Fetch with a short timeout — WA's /membershiplevels is normally
        // sub-second; if it stalls we must NOT block the whole request (which
        // previously hit PHP's 60s max_execution_time in Guzzle and crashed
        // the Members page). On failure, fall back to the long-lived snapshot
        // or an empty list so the caller can degrade gracefully.
        try {
            $r = Http::withToken($this->getAccessToken())
                ->acceptJson()
                ->timeout(8)
                ->connectTimeout(4)
                ->get($this->baseUrl . "/accounts/{$this->getAccountId()}/membershiplevels");

            if ($r->successful()) {
                $levels = $r->json() ?? [];
                if (is_array($levels) && $levels !== []) {
                    Cache::put('wa_levels', $levels, 86400);                // fresh: 24h
                    Cache::put('wa_levels_snapshot', $levels, 86400 * 30);  // last-known-good: 30d
                    return $levels;
                }
            }

            Log::warning('WA getMembershipLevels non-success', ['status' => $r->status()]);
        } catch (\Throwable $e) {
            Log::warning('WA getMembershipLevels failed', ['error' => $e->getMessage()]);
        }

        return Cache::get('wa_levels_snapshot', []);
    }

    // ─── MEMBER PORTAL — MEMBERSHIP LEVEL FEE ───────────────────────────────
    // Returns the annual fee for a membership level, or null if unknown.
    // WildApricot exposes the fee on the membership-level record (the contact's
    // embedded MembershipLevel object only carries Id/Name), so we resolve it
    // from the cached /membershiplevels list.

    public function getMembershipLevelFee(int $levelId): ?float
    {
        try {
            foreach ($this->getMembershipLevels() as $level) {
                if (! is_array($level)) {
                    continue;
                }
                if ((int) ($level['Id'] ?? 0) === $levelId) {
                    $fee = $level['MembershipFee'] ?? null;
                    return $fee !== null ? (float) $fee : null;
                }
            }
        } catch (\Throwable $e) {
            Log::error('WA getMembershipLevelFee exception', ['level_id' => $levelId, 'error' => $e->getMessage()]);
        }
        return null;
    }

    public function resolveLevelId(string $type): int
    {
        $nameMap = [
            'family'                 => 'Family Membership (Primary and Spouse only)',
            'individual'             => 'Individual',
            'flat'                   => 'Flat Membership',
            'checkomatic_family'     => 'Checkomatic Membership (Primary and Spouse only)',
            'checkomatic_individual' => 'Checkomatic',
            'lifetime_family'        => 'Lifetime',
            'lifetime_individual'    => 'Lifetime',
        ];

        $needle = $nameMap[$type] ?? $type;
        $levels = $this->getMembershipLevels();

        // 1st pass — exact match (case-insensitive)
        foreach ($levels as $level) {
            if (strcasecmp(trim($level['Name']), $needle) === 0) return (int) $level['Id'];
        }

        // 2nd pass — partial match
        foreach ($levels as $level) {
            if (stripos($level['Name'], $needle) !== false) return (int) $level['Id'];
        }

        // Nothing found — log all available levels so the admin can fix the name map
        $available = array_column($levels, 'Name');
        Log::error("WA level not found for type='{$type}' needle='{$needle}'. Available levels: " . implode(', ', $available));

        throw new \RuntimeException(
            "Wild Apricot membership level not found for '{$type}'. " .
            "Available levels: " . implode(', ', $available)
        );
    }

    // ─── STEP 1 — CREATE ACTIVE MEMBER ───────────────────────────────────────
    // Called from the Stripe webhook handler AFTER payment is confirmed.
    // We skip PendingNew entirely — Stripe already charged, so we go straight Active.

    public function createActiveMember(array $data): array
    {
        $accountId = $this->getAccountId();
        $levelId   = $this->resolveLevelId($data['membership_type']);
        // dd($accountId,$levelId);
        $payload = [
            'Status'            => 'Active',
            'MembershipEnabled' => true,
            'MemberSince'       => now()->toIso8601String(),
            'RenewalDue'        => $this->calcRenewalDate($data['membership_type'], (int) ($data['duration_years'] ?? 1)),
            'MembershipLevel'   => ['Id' => $levelId],
            'FieldValues'       => $this->buildFieldValues($data),
            'RecreateInvoice' => $data['auto_renewal'] ?? false, // If true, WA will auto-renew next year and generate an invoice (which we won't pay via Stripe, but it keeps the contact in good standing and sends renewal reminders).
        ];

        $r = $this->apiPost("/accounts/{$accountId}/contacts", $payload);
        Log::info('WA createActiveMember response', ['status' => $r->status(), 'body' => $r->json()]);

        if (! $r->successful()) {
            Log::error('WA createActiveMember failed', ['status' => $r->status(), 'body' => $r->body()]);
            throw new \RuntimeException('WA create member failed: ' . $r->body());
        }

        return $r->json();
    }

    // ─── STEP 2 — RECORD STRIPE PAYMENT ────────────────────────────────────
    // Creates the payment in WA (without linking to an invoice yet).
    // Returns the payment array including the WA payment ID.

    /**
     * Returns the Stripe tender ID from WA (cached 24 h).
     */
    public function getCreditCardTenderId(): ?int
    {
        return Cache::remember('wa_credit_card_tender_id', 86400, function () {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/tenders");
            Log::info('WA getTenders response', ['status' => $r->status(), 'body' => $r->body()]);
            if (! $r->successful()) return null;

            $tenders = $r->json();
            if (! is_array($tenders)) return null;

            // Prefer "Stripe" tender, then any credit card tender
            foreach ($tenders as $t) {
                if (strtolower($t['Name'] ?? '') === 'stripe') return (int) $t['Id'];
            }
            foreach ($tenders as $t) {
                $name = strtolower($t['Name'] ?? '');
                if (str_contains($name, 'credit') || str_contains($name, 'card')) {
                    return (int) $t['Id'];
                }
            }
            foreach ($tenders as $t) {
                if (! empty($t['Id'])) return (int) $t['Id'];
            }
            return null;
        });
    }

    // ─── STEP 2 — CREATE INVOICE ─────────────────────────────────────────────

    public function createMembershipInvoice(int $contactId, float $amount, string $membershipType): array
    {
        $accountId   = $this->getAccountId();
        $description = ucwords(str_replace('_', ' ', $membershipType)) . ' Membership';
        $levelId     = $this->resolveLevelId($membershipType);

        $payload = [
            'Value'           => $amount,
            'DocumentDate'    => now()->toDateString(),
            'Contact'         => ['Id' => $contactId],
            'MembershipLevel' => ['Id' => $levelId],
            'OrderType'       => 'ExplicitRenewal',
            'Memo'            => $description,
            'OrderDetails'    => [[
                'Value'           => $amount,
                'OrderDetailType' => 'Membership',
                'Notes'           => $description,
            ]],
        ];

        $r = $this->apiPost("/accounts/{$accountId}/invoices", $payload);
        Log::info('WA createInvoice response', ['status' => $r->status(), 'body' => $r->body()]);

        if (! $r->successful()) {
            Log::error('WA createInvoice failed', ['status' => $r->status(), 'body' => $r->body()]);
            throw new \RuntimeException('WA create invoice failed: ' . $r->body());
        }

        $body = $r->json();
        return is_array($body) ? $body : ['Id' => (int) $body];
    }

    // ─── STEP 3 — RECORD PAYMENT ─────────────────────────────────────────────
    // Two-step approach per the WildApricot API spec:
    //   1. POST /payments              — create the payment record
    //   2. POST /payments/{id}/allocations — AllocateInvoice to mark invoice paid

    public function recordPayment(int $contactId, int $invoiceId, float $amount, string $stripeChargeId, string $stripePaymentMethodId = ''): array
    {
        $accountId = $this->getAccountId();
        $tenderId  = $this->getCreditCardTenderId();

        // ── Step 3a: Create the payment record ───────────────────────────────
        $payload = [
            'Contact'         => ['Id' => $contactId],
            'Value'           => $amount,
            'PaymentType'     => 'CreditCard',
            'Comment'         => 'Stripe charge ID: ' . $stripeChargeId,
            'PaymentMethodID' => $stripePaymentMethodId,
        ];
        if ($tenderId) {
            $payload['Tender'] = ['Id' => $tenderId];
        }

        $r = $this->apiPost("/accounts/{$accountId}/payments", $payload);
        Log::info('WA createPayment response', ['status' => $r->status(), 'body' => $r->body()]);

        if (! $r->successful()) {
            Log::error('WA recordPayment failed', ['body' => $r->body(), 'invoice' => $invoiceId]);
            throw new \RuntimeException('WA record payment failed: ' . $r->body());
        }

        $payment   = is_array($r->json()) ? $r->json() : [];
        $paymentId = (int) ($payment['Id'] ?? 0);

        if (! $paymentId) {
            throw new \RuntimeException('WA record payment: no payment ID in response');
        }

        // ── Step 3b: AllocateInvoice — POST /payments/{paymentId}/allocations ─
        // $allocation = $this->allocateInvoice($accountId, $paymentId, $invoiceId, $amount);

        // Verify settlement
        $ir    = $this->apiGet("/accounts/{$accountId}/invoices/{$invoiceId}");
        $final = $ir->successful() ? $ir->json() : [];
        Log::info('WA invoice settlement check', [
            'invoice_id'  => $invoiceId,
            'payment_id'  => $paymentId,
            // 'allocated'   => $allocation['Value'] ?? $amount,
            'is_paid'     => $final['IsPaid']     ?? 'unknown',
            'paid_amount' => $final['PaidAmount'] ?? 'unknown',
        ]);

        return $payment;
    }

    /**
     * AllocateInvoice — POST /accounts/{accountId}/payments/{paymentId}/allocations
     * Marks the invoice as paid by allocating a payment to it.
     */
    private function allocateInvoice(string $accountId, int $paymentId, int $invoiceId, float $amount): array
    {
        $r = $this->apiPost(
            "/accounts/{$accountId}/payments/{$paymentId}/AllocateInvoice",
            [
                'Invoice' => ['Id' => $invoiceId],
                'Value'   => $amount,
            ]
        );

        Log::info('WA allocateInvoice response', [
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'status'     => $r->status(),
            'body'       => $r->body(),
        ]);

        if (! $r->successful()) {
            Log::error('WA allocateInvoice failed', [
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'body'       => $r->body(),
            ]);
            throw new \RuntimeException("WA allocateInvoice failed for payment {$paymentId} / invoice {$invoiceId}: " . $r->body());
        }

        return is_array($r->json()) ? $r->json() : [];
    }

    // ─── PHONE EXISTENCE CHECK ──────────────────────────────────────────────
    // Queries the WA contact list for any contact whose Phone matches the last
    // 7 digits of the supplied number. Returns true if a match is found.

    public function checkPhoneExists(string $phone): bool
    {
        $digits = preg_replace('/\D/', '', $phone);
        Log::debug('WA checkPhoneExists', ['input' => $phone, 'digits' => $digits]);
        if (strlen($digits) < 10) return false;

        $accountId = $this->getAccountId();
        $last7     = substr($digits, -7);

        $r = $this->apiGet(
            "/accounts/{$accountId}/contacts?" . http_build_query([
                '$filter' => "Phone eq '{$digits}'",
                '$async'  => 'false',
                '$top'    => 1,
            ])
        );

        Log::debug('WA checkPhoneExists', [
            'phone'  => $digits,
            'status' => $r->status(),
        ]);

        if (! $r->successful()) return false;

        $body     = $r->json();
        $contacts = $body['Contacts'] ?? (is_array($body) ? array_filter($body, 'is_array') : []);

        foreach ($contacts as $c) {
            $waPhone = preg_replace('/\D/', '',
                $this->extractFieldValue($c, 'Phone') ?: ($c['Phone'] ?? '')
            );
            if ($waPhone && str_ends_with($waPhone, $last7)) {
                return true;
            }
        }

        return false;
    }

    // ─── MEMBER PORTAL ───────────────────────────────────────────────────────

    public function getContactById(int $contactId): ?array
    {
        try {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/contacts/{$contactId}");
            return $r->successful() ? $r->json() : null;
        } catch (\Throwable $e) {
            Log::error('WA getContactById exception', ['contact_id' => $contactId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ─── MEMBER PORTAL — FIND MEMBER BY EMAIL ───────────────────────────────
    // Looks up a WA contact by email only (no name/phone required).
    // Returns the contact array on success, or null if not found / API error.

    public function findMemberByEmail(string $email): ?array
    {
        try {
            $accountId = $this->getAccountId();
            $safe      = str_replace("'", "''", strtolower(trim($email)));

            $r = $this->apiGet(
                "/accounts/{$accountId}/contacts?" . http_build_query([
                    '$filter' => "Email eq '{$safe}'",
                    '$async'  => 'false',
                    '$top'    => 1,
                ])
            );

            if (! $r->successful()) {
                Log::warning('WA findMemberByEmail: API error', ['status' => $r->status()]);
                return null;
            }

            $body     = $r->json();
            $contacts = $body['Contacts'] ?? (is_array($body) ? array_filter($body, 'is_array') : []);

            if (empty($contacts)) {
                return null;
            }

            $contact   = reset($contacts);
            $contactId = $contact['Id'] ?? null;
            if (! $contactId) return null;

            // Fetch full contact record so FieldValues / membership details are present
            $full = $this->apiGet("/accounts/{$accountId}/contacts/{$contactId}");
            return $full->successful() ? $full->json() : $contact;

        } catch (\Throwable $e) {
            Log::error('WA findMemberByEmail exception', ['email' => $email, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ─── MEMBERSHIP VERIFICATION — SEARCH CONTACT ───────────────────────────
    // Searches WA contacts by email (primary) or first+last name (fallback).
    // Returns the first matching contact array, or null if none found.

    public function searchContact(string $email, string $firstName, string $lastName, string $phone, string $dateOfBirth = ''): ?array
    {
        $accountId = $this->getAccountId();

        // Helper: query contacts with an OData filter, return contacts array
        $query = function (string $filter) use ($accountId): array {
            $r = $this->apiGet(
                "/accounts/{$accountId}/contacts?" . http_build_query([
                    '$filter' => $filter,
                    '$async'  => 'false',
                    '$top'    => 10,
                ])
            );
            Log::debug('WA searchContact response', ['status' => $r->status(), 'body' => substr($r->body(), 0, 2000)]);
            if (! $r->successful()) return [];
            $body = $r->json();
            // WA wraps results in {Contacts:[...]} object
            return $body['Contacts'] ?? (is_array($body) ? array_filter($body, 'is_array') : []);
        };

        $fetchFullContact = function (array $contact) use ($accountId): array {
            $contactId = $contact['Id'] ?? null;
            if (! $contactId) {
                return $contact;
            }

            Log::debug('WA searchContact: fetching full contact to validate candidate', ['contact_id' => $contactId]);
            $r = $this->apiGet("/accounts/{$accountId}/contacts/{$contactId}");
            Log::debug('WA full contact response', ['status' => $r->status(), 'body' => substr($r->body(), 0, 2000)]);

            return $r->successful() ? $r->json() : $contact;
        };

        // Helper: check that a candidate contact matches ALL provided fields
        $matchesAll = function (array $c) use ($email, $firstName, $lastName, $phone, $dateOfBirth): bool {
            // Email must match (case-insensitive)
            if ($email !== '') {
                $waEmail = strtolower(trim($c['Email'] ?? ''));
                if ($waEmail !== strtolower($email)) {
                    Log::debug('WA searchContact: email mismatch', ['expected' => $email, 'got' => $waEmail]);
                    return false;
                }
            }
            // First name must match (case-insensitive)
            if ($firstName !== '') {
                $waFirst = strtolower(trim($c['FirstName'] ?? ''));
                if ($waFirst !== strtolower($firstName)) {
                    Log::debug('WA searchContact: first name mismatch', ['expected' => $firstName, 'got' => $waFirst]);
                    return false;
                }
            }
            // Last name must match (case-insensitive)
            if ($lastName !== '') {
                $waLast = strtolower(trim($c['LastName'] ?? ''));
                if ($waLast !== strtolower($lastName)) {
                    Log::debug('WA searchContact: last name mismatch', ['expected' => $lastName, 'got' => $waLast]);
                    return false;
                }
            }
            // Phone: compare last 7 digits
            // The list endpoint returns Phone at top-level; FieldValues only available on full contact.
            if ($phone !== '') {
                $normalizedInput = preg_replace('/\D/', '', $phone);
                $waPhone = preg_replace('/\D/', '',
                    $this->extractFieldValue($c, 'Phone') ?: ($c['Phone'] ?? '')
                );
                if (! $waPhone || ! str_ends_with($waPhone, substr($normalizedInput, -7))) {
                    Log::debug('WA searchContact: phone mismatch', ['expected' => $phone, 'got_normalized' => $waPhone]);
                    return false;
                }
            }
            if ($dateOfBirth !== '') {
                $expectedDob = $this->normalizeDateOfBirth($dateOfBirth);
                $waDob = $this->normalizeDateOfBirth(
                    $this->extractFieldValue($c, 'Date of Birth') ?: $this->extractFieldValue($c, 'custom-10694881')
                );
                if (! $expectedDob || ! $waDob || $waDob !== $expectedDob) {
                    Log::debug('WA searchContact: date of birth mismatch', [
                        'expected' => $expectedDob ?: $dateOfBirth,
                        'got' => $waDob,
                    ]);
                    return false;
                }
            }
            return true;
        };

        $contact = null;

        // 1. Search by email (most precise), then validate all other fields
        if ($email !== '') {
            $safe     = str_replace("'", "''", $email); // OData single-quote escape
            $contacts = $query("Email eq '{$safe}'");
            foreach ($contacts as $c) {
                $fullContact = $fetchFullContact($c);
                if ($matchesAll($fullContact)) { $contact = $fullContact; break; }
            }
        }

        // 2. Search by first + last name, then validate email and phone
        if (! $contact && $firstName !== '' && $lastName !== '') {
            $fn       = str_replace("'", "''", $firstName);
            $ln       = str_replace("'", "''", $lastName);
            $contacts = $query("FirstName eq '{$fn}' AND LastName eq '{$ln}'");
            foreach ($contacts as $c) {
                $fullContact = $fetchFullContact($c);
                if ($matchesAll($fullContact)) { $contact = $fullContact; break; }
            }
        }

        if (! $contact) return null;

        // Always fetch the full contact by ID — the list endpoint returns a subset of fields
        // and does not guarantee MemberSince, RenewalDue, or FieldValues are present.
        return $contact;
    }

    private function normalizeDateOfBirth(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['m/d/Y', 'Y-m-d', DATE_ATOM] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extract a named field value from a WA contact's FieldValues array.
     * Handles both scalar values and WA choice objects {"Id":…,"Label":…}.
     */
    public function extractFieldValue(array $contact, string $fieldName): string
    {
        foreach ($contact['FieldValues'] ?? [] as $fv) {
            if (($fv['FieldName'] ?? '') === $fieldName || ($fv['SystemCode'] ?? '') === $fieldName) {
                $val = $fv['Value'] ?? '';
                return is_array($val) ? ($val['Label'] ?? '') : (string) $val;
            }
        }
        return '';
    }

    // ─── UPDATE MEMBER ───────────────────────────────────────────────────────
    // PUT /accounts/{accountId}/contacts/{contactId}
    // Updates an existing contact's profile and membership fields.
    // Only fields included in $data are changed; omitted keys are left as-is
    // because we merge over the current contact fetched from WA first.

    public function updateMember(int $contactId, array $data): array
    {
        $accountId = $this->getAccountId();

        // Fetch current contact so we preserve fields we are not updating
        $current = $this->apiGet("/accounts/{$accountId}/contacts/{$contactId}");
        if (! $current->successful()) {
            throw new \RuntimeException("WA updateMember: could not fetch contact {$contactId}: " . $current->body());
        }
        $existing = $current->json();

        $payload = ['Id' => $contactId];

        // Membership level — only change if a new type is provided
        if (! empty($data['membership_type'])) {
            $levelId = $this->resolveLevelId($data['membership_type']);
            $payload['MembershipLevel'] = ['Id' => $levelId];
            // An explicit renewal_due (used by the renewal flow) wins; otherwise
            // fall back to calcRenewalDate (used by signup).
            $payload['RenewalDue']      = ! empty($data['renewal_due'])
                ? $data['renewal_due']
                : $this->calcRenewalDate($data['membership_type']);
        } else {
            // Preserve existing level and renewal date
            if (! empty($existing['MembershipLevel'])) {
                $payload['MembershipLevel'] = $existing['MembershipLevel'];
            }
            if (! empty($existing['RenewalDue'])) {
                $payload['RenewalDue'] = $existing['RenewalDue'];
            }
        }

        // Status — default to keeping the existing value
        $payload['Status']            = $data['status']            ?? ($existing['Status']            ?? 'Active');
        $payload['MembershipEnabled'] = $data['membership_enabled'] ?? ($existing['MembershipEnabled'] ?? true);

        // Build updated FieldValues, merging new values over existing ones
        $existingFVMap = [];
        foreach ($existing['FieldValues'] ?? [] as $fv) {
            $key = $fv['SystemCode'] ?? $fv['FieldName'] ?? null;
            if ($key) $existingFVMap[$key] = $fv;
        }

        foreach ($this->buildFieldValues($data) as $newFv) {
            $key = $newFv['SystemCode'] ?? $newFv['FieldName'] ?? null;
            if ($key) $existingFVMap[$key] = $newFv;
        }

        $payload['FieldValues'] = $this->stripStalePictureFields(array_values($existingFVMap));

        $r = $this->apiPut("/accounts/{$accountId}/contacts/{$contactId}", $payload);
        Log::info('WA updateMember response', ['contact_id' => $contactId, 'status' => $r->status(), 'body' => $r->body()]);

        if (! $r->successful()) {
            // WA sometimes 400s with "Picture with id '…' not found" when the
            // contact's stored Picture ID references an image that has since
            // been deleted server-side. Retry once with that field removed.
            if ($this->isStalePictureError($r->body())) {
                $payload['FieldValues'] = $this->stripStalePictureFields($payload['FieldValues'], force: true);
                $r = $this->apiPut("/accounts/{$accountId}/contacts/{$contactId}", $payload);
                Log::info('WA updateMember retry without Picture ID', ['contact_id' => $contactId, 'status' => $r->status()]);
            }
        }

        if (! $r->successful()) {
            Log::error('WA updateMember failed', ['contact_id' => $contactId, 'status' => $r->status(), 'body' => $r->body()]);
            throw new \RuntimeException("WA update member failed for contact {$contactId}: " . $r->body());
        }

        return $r->json() ?? [];
    }

    // ─── STEP 4 — ADD RELATED CONTACT (spouse / flat family member) ──────────
    // Creates the contact and links them into the primary's WA Bundle so they
    // appear under "Members" with IsMember=true.
    //
    // $bundleId  — the BundleId field from the primary contact's creation response
    //              (FieldValues SystemCode="BundleId"). WA requires the bundle ID,
    //              NOT the coordinator contact ID, for MemberGroup linking.
    //              Falls back to $primaryContactId if bundle ID is unavailable.

    public function addRelatedContact(int $primaryContactId, int $bundleId, int $levelId, array $data): array
    {
        $accountId = $this->getAccountId();

        // WA docs: to link a bundle member, set MemberRole="Bundle member" and
        // BundleId = primary's bundle ID via FieldValues.
        // Only applicable when the level is a bundle type (bundleId > 0).
        // Flat membership levels are non-bundle — members are created independently.
        $bundleFieldValues = $bundleId > 0 ? [
            ['FieldName' => 'Member role', 'SystemCode' => 'MemberRole', 'Value' => ['Label' => 'Bundle member']],
            ['FieldName' => 'Bundle ID',   'SystemCode' => 'BundleId',   'Value' => $bundleId],
        ] : [];

        $payload = [
            'Status'            => 'Active',
            'MembershipEnabled' => true,
            'MembershipLevel'   => ['Id' => $levelId],
            'FieldValues'       => array_merge($this->buildFieldValues($data), $bundleFieldValues),
        ];

        Log::debug('WA addRelatedContact payload', [
            'bundle_id'    => $bundleId,
            'level_id'     => $levelId,
            'bundle_linked' => $bundleId > 0,
        ]);

        $r = $this->apiPost("/accounts/{$accountId}/contacts", $payload);
        Log::info('WA addRelatedContact response', ['status' => $r->status(), 'body' => $r->body()]);

        if (! $r->successful()) {
            Log::error('WA addRelatedContact failed', ['body' => $r->body()]);
            throw new \RuntimeException('WA add related contact failed: ' . $r->body());
        }

        return $r->json();
    }

    // ─── UPLOAD CONTACT PICTURE / ID-CARD ─────────────────────────────────────
    // WildApricot's v2.x API has no working endpoint for FileAttachment-type
    // custom fields, so we don't try to upload the binary into WA. Instead:
    //
    //   1. Save the uploaded file to local storage (storage/app/public/...)
    //      under a contact-scoped path.
    //   2. Build the public URL via Storage::url() / asset() — which works
    //      because of `php artisan storage:link`.
    //   3. PUT that URL into the WA contact's "Picture URL" text field
    //      (SystemCode in self::PICTURE_URL_FIELD_CODE) using the existing
    //      patchContactField helper.
    //
    // Staff can then open the ID card from the WA admin or our portal by
    // clicking the URL. The file lives on our server, the WA contact carries
    // only the reference.

    public function uploadContactPicture(int $contactId, \Illuminate\Http\UploadedFile $file): void
    {
        // 1. Sanitize filename and persist to public storage. Contact-scoped
        //    directory so each member's IDs are easy to find.
        $rawName = $file->getClientOriginalName() ?: 'upload';
        $ext     = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'bin'));
        $stem    = pathinfo($rawName, PATHINFO_FILENAME);
        $safe    = preg_replace('/[^A-Za-z0-9._-]+/', '_', $stem) ?: 'upload';
        // Add a short random suffix so re-uploads don't overwrite earlier files
        // (handy when staff want to inspect a member's previous submission).
        $stored  = $safe . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $dir     = "member-ids/{$contactId}";

        $path = \Illuminate\Support\Facades\Storage::disk('public')
            ->putFileAs($dir, $file, $stored);

        if ($path === false || $path === '') {
            throw new \RuntimeException("uploadContactPicture: failed to persist file for contact {$contactId}");
        }

        // 2. Build a public URL. Storage::url may return either a path-only
        //    string ('/storage/...') or a fully-qualified URL depending on the
        //    disk config; normalize to absolute by prepending APP_URL only
        //    when the returned value isn't already absolute.
        $rawUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        $publicUrl = preg_match('#^https?://#i', $rawUrl)
            ? $rawUrl
            : rtrim((string) config('app.url'), '/') . (str_starts_with($rawUrl, '/') ? '' : '/') . $rawUrl;

        Log::info('uploadContactPicture: file saved locally', [
            'contact_id'  => $contactId,
            'stored_path' => $path,
            'public_url'  => $publicUrl,
            'byte_size'   => $file->getSize(),
            'mime'        => $file->getMimeType(),
        ]);

        // 3. Write the URL to the WA contact's "Picture URL" text field. The
        //    helper merges this FieldValue into the existing contact and PUTs
        //    it back, with stale-picture and Picture-ID retry guards already
        //    in place from the previous bug fixes.
        $this->patchContactField(
            $contactId,
            self::PICTURE_URL_FIELD_CODE,
            'Picture URL',
            $publicUrl,
        );
    }

    /**
     * Same end-result as uploadContactPicture(), but the source file already
     * lives on the 'local' disk (registration flow stores ID cards there
     * before payment confirmation). Copies the file into the public disk
     * under member-ids/{contactId}/ and writes the resulting public URL into
     * the contact's "Picture URL" custom field.
     */
    public function uploadIdCardFromLocalPath(int $contactId, string $localRelativePath): void
    {
        $localDisk  = \Illuminate\Support\Facades\Storage::disk('local');
        $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');

        if (! $localDisk->exists($localRelativePath)) {
            Log::warning('uploadIdCardFromLocalPath: source not found', [
                'contact_id' => $contactId,
                'path'       => $localRelativePath,
            ]);
            return;
        }

        $rawName = basename($localRelativePath) ?: 'upload';
        $ext     = strtolower(pathinfo($rawName, PATHINFO_EXTENSION) ?: 'bin');
        $stem    = pathinfo($rawName, PATHINFO_FILENAME) ?: 'upload';
        $safe    = preg_replace('/[^A-Za-z0-9._-]+/', '_', $stem) ?: 'upload';
        $stored  = $safe . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $dir     = "member-ids/{$contactId}";

        $bytes = $localDisk->get($localRelativePath);
        $publicPath = $dir . '/' . $stored;
        if (! $publicDisk->put($publicPath, $bytes)) {
            throw new \RuntimeException("uploadIdCardFromLocalPath: failed to persist file for contact {$contactId}");
        }

        $rawUrl = $publicDisk->url($publicPath);
        $publicUrl = preg_match('#^https?://#i', $rawUrl)
            ? $rawUrl
            : rtrim((string) config('app.url'), '/') . (str_starts_with($rawUrl, '/') ? '' : '/') . $rawUrl;

        Log::info('uploadIdCardFromLocalPath: file copied to public disk', [
            'contact_id'   => $contactId,
            'source_path'  => $localRelativePath,
            'stored_path'  => $publicPath,
            'public_url'   => $publicUrl,
            'byte_size'    => strlen($bytes),
        ]);

        $this->patchContactField(
            $contactId,
            self::PICTURE_URL_FIELD_CODE,
            'Picture URL',
            $publicUrl,
        );
    }

    /**
     * Re-encode unsupported image formats to PNG so WA's /pictures endpoint
     * accepts them. WA rejects WebP (and other modern formats) with an
     * empty-body 400. JPEG/PNG/GIF/BMP pass through unchanged.
     *
     * @return array{0:string,1:string,2:string}|null  [bytes, mime, filename] when converted, null when no conversion was needed or possible
     */
    private function convertImageBytesToSupported(string $bytes, string $mime, string $clientName): ?array
    {
        $supported = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp'];
        if (in_array(strtolower($mime), $supported, true)) {
            return null;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            Log::warning('WA uploadContactPicture: GD missing, cannot convert image format', ['mime' => $mime]);
            return null;
        }

        // imagecreatefromstring auto-detects WebP, JPEG, PNG, GIF, BMP — so a
        // single call handles every format GD knows about on this PHP build.
        $im = @imagecreatefromstring($bytes);
        if ($im === false) {
            Log::warning('WA uploadContactPicture: image decode failed, sending original bytes', ['mime' => $mime]);
            return null;
        }

        ob_start();
        $ok = imagepng($im, null, 6);
        $pngBytes = ob_get_clean();
        imagedestroy($im);

        if (! $ok || $pngBytes === '' || $pngBytes === false) {
            Log::warning('WA uploadContactPicture: PNG encode failed, sending original bytes', ['mime' => $mime]);
            return null;
        }

        // Rewrite the filename's extension so WA stores it as .png too.
        $stem = pathinfo($clientName, PATHINFO_FILENAME) ?: 'upload';
        return [$pngBytes, 'image/png', $stem . '.png'];
    }

    /**
     * Pulls the uploaded picture's id out of a WA /pictures response.
     * WA has been observed returning the key inconsistently — sometimes
     * `picture0`, sometimes the literally-quoted `"picture0"`. Rather than
     * depend on the key, take the first non-empty value in the map.
     */
    private function extractUploadedPictureId(mixed $response): ?string
    {
        if (! is_array($response)) {
            return null;
        }
        foreach ($response as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    /**
     * SystemCode of the WA "Picture ID" custom field (FieldType: Picture).
     * NOTE: Recreating the field in WA admin assigns a new SystemCode. If the
     * field is recreated, look up the new code with:
     *   php artisan tinker --execute="..."  (see getFieldSystemCode helper)
     * or call $wa->getFieldSystemCode('Picture ID') and update this constant.
     *
     * This field is no longer the upload target (FileAttachment-type fields
     * have no working API), but the constant is retained so the stale-picture
     * retry helpers and historic FieldValue strippers keep working on contacts
     * that still carry an old Picture ID value.
     */
    private const PICTURE_ID_FIELD_CODE = 'custom-17976996';

    /**
     * SystemCode of the WA "Picture URL" custom field (FieldType: Text).
     * uploadContactPicture() saves the uploaded file locally and PUTs the
     * resulting public URL into this field, so it can be opened from WA
     * admin or our staff portal.
     */
    private const PICTURE_URL_FIELD_CODE = 'custom-17977092';

    /**
     * Stores an uploaded picture in the contact's "Picture ID" custom field
     * (Picture-type field, SystemCode custom-17827238) — NOT the contact's
     * built-in profile Photo. The ID card belongs in this field.
     *
     * A WA Picture-type FieldValue takes the uploaded picture as {"Id": <id>}.
     * The whole contact is fetched, the FieldValue merged, then PUT back.
     *
     * PUT /accounts/{accountId}/contacts/{contactId}
     */
    private function setContactPictureIdField(int $contactId, string $pictureId): void
    {
        $accountId = $this->getAccountId();

        $r = $this->apiGet("/accounts/{$accountId}/contacts/{$contactId}");
        if (! $r->successful()) {
            throw new \RuntimeException("WA setContactPictureIdField: fetch failed for contact {$contactId}: " . $r->body());
        }

        $contact = $r->json();

        // Build the full Picture FieldValue. WA stores Picture-type values as
        // {Id: <filename>, Url: <full-pictures-url>}. The PUT accepts an
        // Id-only payload with status 200, but the admin UI then fails to
        // resolve the picture and renders the field empty. Supplying both
        // Id AND Url makes the FieldValue render correctly.
        $pictureUrl = "{$this->baseUrl}/accounts/{$accountId}/Pictures/{$pictureId}";
        $pictureValue = ['Id' => $pictureId, 'Url' => $pictureUrl];

        // Merge the picture into the "Picture ID" FieldValue, replacing any
        // existing entry for that SystemCode. Also drop OTHER Picture-type
        // FieldValues whose stored Url references a now-deleted image — WA
        // validates every echoed Picture FieldValue and 400s the whole PUT
        // when it can't resolve one (e.g. the previous Picture ID field was
        // deleted and recreated, leaving a stale entry on the contact).
        $fieldValues = [];
        $found       = false;
        foreach ($contact['FieldValues'] ?? [] as $fv) {
            $isOurField = ($fv['SystemCode'] ?? '') === self::PICTURE_ID_FIELD_CODE;
            if ($isOurField) {
                $fv['Value'] = $pictureValue;
                $found = true;
                $fieldValues[] = $fv;
                continue;
            }
            // Drop any other Picture-typed FieldValue (a Value with an Id+Url
            // shape pointing at /Pictures/). Non-picture fields are echoed
            // back unchanged.
            $val = $fv['Value'] ?? null;
            $isPictureValue = is_array($val)
                && isset($val['Url'])
                && is_string($val['Url'])
                && str_contains($val['Url'], '/Pictures/');
            if ($isPictureValue) {
                Log::info('WA setContactPictureIdField: dropping stale picture FieldValue', [
                    'contact_id'  => $contactId,
                    'system_code' => $fv['SystemCode'] ?? null,
                    'stale_url'   => $val['Url'],
                ]);
                continue;
            }
            $fieldValues[] = $fv;
        }
        if (! $found) {
            $fieldValues[] = [
                'FieldName'  => 'Picture ID',
                'SystemCode' => self::PICTURE_ID_FIELD_CODE,
                'Value'      => $pictureValue,
            ];
        }
        $contact['FieldValues'] = $fieldValues;

        $rp = $this->apiPut("/accounts/{$accountId}/contacts/{$contactId}", $contact);
        Log::info('WA setContactPictureIdField response', [
            'contact_id' => $contactId,
            'picture_id' => $pictureId,
            'status'     => $rp->status(),
            'body'       => substr($rp->body(), 0, 500),
        ]);

        // If WA still 400s with the stale-picture error (e.g. an exotic edge
        // case my filter missed), retry once with EVERY Picture-typed
        // FieldValue stripped so the write can succeed.
        if (! $rp->successful() && $this->isStalePictureError($rp->body())) {
            $contact['FieldValues'] = array_values(array_filter($fieldValues, function ($fv) use ($pictureId) {
                // Keep our own write (it has the brand-new Id, no Url) and
                // every non-picture field. Drop anything else whose Value
                // looks like a picture reference.
                $val = $fv['Value'] ?? null;
                if (! is_array($val)) return true;
                if (($fv['SystemCode'] ?? '') === self::PICTURE_ID_FIELD_CODE) return true;
                $looksLikePicture = isset($val['Url'])
                    && is_string($val['Url'])
                    && str_contains($val['Url'], '/Pictures/');
                return ! $looksLikePicture;
            }));
            $rp = $this->apiPut("/accounts/{$accountId}/contacts/{$contactId}", $contact);
            Log::info('WA setContactPictureIdField retry without stale pictures', [
                'contact_id' => $contactId,
                'status'     => $rp->status(),
            ]);
        }

        if (! $rp->successful()) {
            throw new \RuntimeException("WA setContactPictureIdField failed for contact {$contactId}: " . $rp->body());
        }
    }

    // ─── TARGETED FIELD PATCHES ──────────────────────────────────────────────

    /**
     * Stamps the WA Invoice# field on the contact after the invoice is created.
     * SystemCode: custom-17858555
     */
    public function setInvoiceNumberOnContact(int $contactId, int $invoiceId): void
    {
        $this->patchContactField($contactId, 'custom-17858555', 'Invoice#', (string) $invoiceId);
    }

    /**
     * Marks Payment Processed = true and records the Stripe charge ID as Proof Of Payment.
     * Both fields are written in a single PUT call.
     * SystemCodes: custom-10357567 (Payment Processed), custom-17207638 (Proof Of Payment)
     */
    public function setPaymentProcessedOnContact(int $contactId, string $chargeId = ''): void
    {
        $this->patchContactFields($contactId, [
            ['FieldName' => 'Payment Processed', 'SystemCode' => 'custom-10357567', 'Value' => true],
            ['FieldName' => 'Proof Of Payment',  'SystemCode' => 'custom-17207638', 'Value' => $chargeId],
        ]);
    }

    /**
     * Fetches the contact, replaces one FieldValue by SystemCode, then PUTs it back.
     */
    private function patchContactField(int $contactId, string $systemCode, string $fieldName, mixed $value): void
    {
        $this->patchContactFields($contactId, [
            ['FieldName' => $fieldName, 'SystemCode' => $systemCode, 'Value' => $value],
        ]);
    }

    /**
     * Fetches the contact, merges one or more FieldValues by SystemCode, then PUTs it back.
     * $fields: array of ['FieldName' => ..., 'SystemCode' => ..., 'Value' => ...]
     */
    private function patchContactFields(int $contactId, array $fields): void
    {
        $accountId = $this->getAccountId();

        $r = $this->apiGet("/accounts/{$accountId}/contacts/{$contactId}");
        if (! $r->successful()) {
            throw new \RuntimeException("WA patchContactFields: fetch failed for contact {$contactId}: " . $r->body());
        }
        $contact = $r->json();

        $fvMap = [];
        foreach ($contact['FieldValues'] ?? [] as $fv) {
            $key = $fv['SystemCode'] ?? $fv['FieldName'] ?? null;
            if ($key) $fvMap[$key] = $fv;
        }
        foreach ($fields as $field) {
            $fvMap[$field['SystemCode']] = $field;
        }

        $payload = array_merge($contact, [
            'FieldValues' => $this->stripStalePictureFields(array_values($fvMap)),
        ]);

        $r = $this->apiPut("/accounts/{$accountId}/contacts/{$contactId}", $payload);
        $names = implode(', ', array_column($fields, 'FieldName'));
        Log::info("WA patchContactFields [{$names}]", [
            'contact_id' => $contactId,
            'status'     => $r->status(),
        ]);

        if (! $r->successful() && $this->isStalePictureError($r->body())) {
            $payload['FieldValues'] = $this->stripStalePictureFields($payload['FieldValues'], force: true);
            $r = $this->apiPut("/accounts/{$accountId}/contacts/{$contactId}", $payload);
            Log::info("WA patchContactFields [{$names}] retry without Picture ID", [
                'contact_id' => $contactId,
                'status'     => $r->status(),
            ]);
        }

        if (! $r->successful()) {
            throw new \RuntimeException("WA patchContactFields [{$names}] failed: " . $r->body());
        }
    }

    /**
     * Detect WA's "Picture with id '…' not found" 400 response so we know to
     * retry without echoing back the stale Picture ID FieldValue.
     */
    private function isStalePictureError(?string $body): bool
    {
        if (! $body) return false;
        return str_contains($body, "Picture with id") && str_contains($body, "not found");
    }

    /**
     * Remove Picture-type FieldValues whose stored Url/Id refers to a picture
     * WA can no longer resolve. By default this is a no-op (we leave the field
     * in the payload, since most updates succeed). When $force is true, EVERY
     * picture-typed FieldValue is dropped — regardless of which SystemCode it
     * lives under — so WA leaves any existing value in place.
     *
     * The match is by Value shape, not SystemCode: a Picture FieldValue has
     * `Value` of the form {Id: <filename>, Url: "https://.../Pictures/..."}.
     * Matching this way catches values left over from a deleted-and-recreated
     * Picture ID field (different SystemCode) which would otherwise still
     * trigger WA's "Picture not found" 400 on every contact PUT.
     *
     * @param  array<int,array<string,mixed>>  $fieldValues
     * @return array<int,array<string,mixed>>
     */
    private function stripStalePictureFields(array $fieldValues, bool $force = false): array
    {
        if (! $force) return $fieldValues;
        return array_values(array_filter($fieldValues, function ($fv) {
            $val = $fv['Value'] ?? null;
            if (! is_array($val)) return true;
            $looksLikePicture = isset($val['Url'])
                && is_string($val['Url'])
                && str_contains($val['Url'], '/Pictures/');
            return ! $looksLikePicture;
        }));
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    private function buildFieldValues(array $data): array
    {
        $fields = [];

        $phoneDigits = preg_replace('/\D/', '', $data['phone'] ?? '') ?: null;
        $data['phone'] = $phoneDigits;

        // Standard system fields
        $standard = [
            'FirstName' => $data['first_name'] ?? null,
            'LastName'  => $data['last_name']  ?? null,
            'Email'     => $data['email']      ?? null,
            'Phone'     => $phoneDigits,
        ];
        foreach ($standard as $code => $val) {
            if ($val !== null && $val !== '') {
                $fields[] = ['FieldName' => $code, 'SystemCode' => $code, 'Value' => $val];
            }
        }

        // Custom fields — system codes confirmed from live WA account response
        $custom = [
            ['FieldName' => 'Middle Name',    'SystemCode' => 'custom-8524218',  'key' => 'middle_name'],
            ['FieldName' => 'Cell Phone',     'SystemCode' => 'custom-9967571',  'key' => 'phone'],
            ['FieldName' => 'Street Address', 'SystemCode' => 'custom-9967566',  'key' => 'street'],
            ['FieldName' => 'City',           'SystemCode' => 'custom-9967567',  'key' => 'city'],
            ['FieldName' => 'State',          'SystemCode' => 'custom-9967569',  'key' => 'state'],
            ['FieldName' => 'ZIP',            'SystemCode' => 'custom-9967570',  'key' => 'zip'],
            ['FieldName' => 'Date of Birth',  'SystemCode' => 'custom-10694881', 'key' => 'dob'],
            ['FieldName' => 'TX DL/ID Number','SystemCode' => 'custom-17846913', 'key' => 'tx_dl'],
        ];

        // Terms & Rules acceptance — sent for primary and every related contact (spouses, flat members).
        // Uses dynamic field lookup so no hardcoded system code is required.
        $termsAgreedAt = $data['terms_agreed_at'] ?? null;
        if ($termsAgreedAt !== null && $termsAgreedAt !== '') {
            $termsSystemCode = $this->getFieldSystemCode('Terms & Rules');
            if ($termsSystemCode) {
                $fields[] = [
                    'FieldName'  => 'Terms & Rules',
                    'SystemCode' => $termsSystemCode,
                    'Value'      => true,
                ];
            }
        }

        // Member Identifier — stores the primary member's WA contact ID on flat members
        // so they can be linked back to their primary contact.
        $memberIdentifier = $data['member_identifier'] ?? null;
        if ($memberIdentifier !== null && $memberIdentifier !== '' && $memberIdentifier !== 0) {
            $systemCode = $this->getFieldSystemCode('Member Identifier');
            if ($systemCode) {
                $fields[] = [
                    'FieldName'  => 'Member Identifier',
                    'SystemCode' => $systemCode,
                    'Value'      => (string) $memberIdentifier,
                ];
            }
        }

        // Invoice# — stores the WA invoice ID on spouses and flat members so they can
        // be linked back to the invoice created under the primary contact.
        $invoiceNumber = $data['invoice_number'] ?? null;
        if ($invoiceNumber !== null && $invoiceNumber !== '' && $invoiceNumber !== 0) {
            $systemCode = $this->getFieldSystemCode('Invoice#');
            if ($systemCode) {
                $fields[] = [
                    'FieldName'  => 'Invoice#',
                    'SystemCode' => $systemCode,
                    'Value'      => (string) $invoiceNumber,
                ];
            }
        }
        foreach ($custom as $field) {
            $val = $data[$field['key']] ?? null;
            if ($val !== null && $val !== '') {
                $fields[] = ['FieldName' => $field['FieldName'], 'SystemCode' => $field['SystemCode'], 'Value' => $val];
            }
        }

        // Gender — system code confirmed from live WA account response
        if (! empty($data['gender'])) {
            $fields[] = ['FieldName' => 'Gender', 'SystemCode' => 'custom-9967238', 'Value' => $data['gender']];
        }

        // Role — WA dropdown field: must send {"Id": choiceId} not a plain string.
        $roleLabel = $data['role'] ?? '';
        if ($roleLabel !== '') {
            $roleChoices = $this->getRoleChoices();
            $roleId      = $roleChoices[$roleLabel] ?? null;

            if ($roleId) {
                $fields[] = [
                    'FieldName'  => 'Role',
                    'SystemCode' => 'custom-16727578',
                    'Value'      => ['Id' => $roleId, 'Label' => $roleLabel],
                ];
            } else {
                Log::warning('WA role label not found in choices — skipping role field', [
                    'role_label'      => $roleLabel,
                    'available_roles' => array_keys($roleChoices),
                ]);
            }
        }

        // Zone / Center — WA dropdown field: must send {"Id": choiceId} not a plain string.
        $zoneLabel = $data['zone'] ?? '';
        if ($zoneLabel !== '') {
            $choices  = $this->getZoneChoices();
            $choiceId = $choices[$zoneLabel] ?? null;

            if ($choiceId) {
                $fields[] = [
                    'FieldName'  => 'Zone / Center',
                    'SystemCode' => 'custom-9967573',
                    'Value'      => ['Id' => $choiceId, 'Label' => $zoneLabel],
                ];
            } else {
                Log::warning('WA zone label not found in choices — skipping zone field', [
                    'zone_label'      => $zoneLabel,
                    'available_zones' => array_keys($choices),
                ]);
            }
        }

        // Renewal due — only set when a membership type is provided (e.g. signup /
        // level change). Profile edits omit membership_type and must not touch it.
        // Lifetime levels have no renewal date — calcRenewalDate yields 'Never',
        // which WA rejects as a FieldValue DateTime, so the field is omitted
        // entirely (WA derives "never expires" from the membership level itself).
        if (! empty($data['membership_type'])) {
            $renewalDue = $this->calcRenewalDate($data['membership_type']);
            if ($renewalDue !== 'Never') {
                $fields[] = ['FieldName' => 'Renewal due', 'SystemCode' => 'RenewalDue', 'Value' => $renewalDue];
            }
        }

        return $fields;
    }

    // ─── DASHBOARD DATA ──────────────────────────────────────────────────────

    /**
     * Fetches dashboard statistics using only fast $count=true calls — no contact pages fetched.
     * Each call returns {"Count": N} in ~1s. Total: ~15 calls = ~15-20s, cached 1 hour.
     *
     * Zone/ZIP aggregation via per-zone-choice count calls using the cached zone choice list.
     * ZIP breakdown is omitted (requires fetching all contacts — too slow at 6000+ members).
     */
    /**
     * Reads dashboard data from the DB and returns the array shape the controller expects.
     * Returns null if the DB has never been populated (command hasn't run yet).
     */
    public function getDashboardFromDb(): ?array
    {
        $stat = \App\Models\DashboardStat::current();
        if (! $stat->exists) {
            return null;
        }

        $centers = \App\Models\DashboardCenter::with('zips')->get();

        // Build zones array
        $zoneMap = [];
        $globalZipCount = [];
        $globalZipCity  = [];

        foreach ($centers as $center) {
            $zoneName   = $center->zone_name;
            $centerName = $center->center_name;

            // Every "Total Members" surface on the dashboard (top card, per-zone
            // pill, per-center pill, ZIP table) is now scoped to ACTIVE members
            // only. The level breakdown and per-zone ZIP page-through in
            // syncDashboardToDb() already filter Status eq 'Active', so the
            // existing stored values are active-only; we just need to use
            // active_members here instead of member_count.
            $zoneMap[$zoneName] ??= ['members' => 0, 'centers' => []];
            $zoneMap[$zoneName]['members'] += $center->active_members;

            $zips = [];
            foreach ($center->zips->sortByDesc('member_count') as $zipRow) {
                $zips[] = ['code' => $zipRow->zip, 'count' => $zipRow->member_count];
                $globalZipCount[$zipRow->zip] = ($globalZipCount[$zipRow->zip] ?? 0) + $zipRow->member_count;
                if ($zipRow->city && ! isset($globalZipCity[$zipRow->zip])) {
                    $globalZipCity[$zipRow->zip] = $zipRow->city;
                }
            }

            $zoneMap[$zoneName]['centers'][] = [
                'name'            => $centerName,
                'img'             => 'mosque.png',
                // 'total' = active-only (used by every per-center / per-zone
                // pill). 'member_count' = raw WA member count (active + lapsed
                // + …) — only used to recompute the top "Total Members" card
                // for zone-/center-scoped users.
                'total'           => $center->active_members,
                'member_count'    => $center->member_count,
                'active'          => $center->active_members,
                'lapsed'          => $center->lapsed_members,
                'individual'      => $center->individual_members,
                'checkmatic'      => $center->checkmatic_members,
                'lifetime'        => $center->lifetime_members,
                'level_breakdown' => $center->level_breakdown ?? [],
                'zips'            => $zips,
            ];
        }

        $zones = [];
        foreach ($zoneMap as $zoneName => $zoneData) {
            $sortedCenters = $zoneData['centers'];
            usort($sortedCenters, fn($a, $b) => $b['total'] <=> $a['total']);
            $zones[] = [
                'name'    => $zoneName,
                'members' => $zoneData['members'],
                'masjids' => count($sortedCenters),
                'centers' => $sortedCenters,
            ];
        }
        usort($zones, fn($a, $b) => $b['members'] <=> $a['members']);

        arsort($globalZipCount);
        $zipData = collect(array_map(
            fn($zip, $n) => ['zip' => $zip, 'city' => $globalZipCity[$zip] ?? '', 'count' => $n],
            array_keys($globalZipCount),
            array_values($globalZipCount)
        ));

        $total     = $stat->total_members;
        $active    = $stat->active_members;
        $lapsed    = $stat->lapsed_members;
        // Profile Status panel is an Active-vs-Lapsed comparison, so the
        // percentages are scoped to the two groups (not the raw WA total).
        $compTotal = $active + $lapsed;
        $activePct = $compTotal > 0 ? (int) round($active / $compTotal * 100) : 0;

        return [
            // Top "Total Members" card shows the full WA member count
            // (active + lapsed + others). The Active and Lapsed cards next to
            // it break that total down. Every other surface on the dashboard
            // (zones, centers, level breakdown, ZIPs) is active-only.
            'stats'          => ['total' => $total, 'active' => $active, 'lapsed' => $lapsed],
            'levelBreakdown' => $stat->level_breakdown ?? [
                ['name' => 'Individual', 'count' => $stat->individual_members],
                ['name' => 'Checkomatic', 'count' => $stat->checkmatic_members],
                ['name' => 'Lifetime', 'count' => $stat->lifetime_members],
            ],
            'profileStatus'  => [
                'active'     => $active,
                'lapsed'     => $lapsed,
                'active_pct' => $activePct,
                'lapsed_pct' => 100 - $activePct,
            ],
            'zipStats'       => ['total' => $stat->total_zips],
            'zipData'        => $zipData,
            'zones'          => $zones,
            'last_synced_at' => $stat->last_synced_at,
        ];
    }

    /**
     * Fetches fresh data from WildApricot and writes it to the DB.
     * Called by the portal:sync-dashboard Artisan command hourly.
     */
    public function syncDashboardToDb(): void
    {
        $accountId = $this->getAccountId();

        // Fast count helper
        $countOf = fn(string $filter): int => (function () use ($accountId, $filter) {
            $r = $this->apiGet(
                "/accounts/{$accountId}/contacts?" . http_build_query([
                    '$async'  => 'false',
                    '$filter' => $filter,
                    '$count'  => 'true',
                ])
            );
            return $r->successful() ? (int) ($r->json()['Count'] ?? 0) : 0;
        })();

            // ── Stats ────────────────────────────────────────────────────────────
            $total  = $countOf('Member eq true');
            $active = $countOf("Member eq true AND Status eq 'Active'");
            $lapsed = $countOf("Member eq true AND Status eq 'Lapsed'");

            // ── Level breakdown — active members only, one count per level ─────────
            $individual     = 0;
            $checkmatic     = 0;
            $lifetime       = 0;
            $levelBreakdown = []; // [{name, count}] for all levels
            foreach ($this->getMembershipLevels() as $level) {
                $name = $level['Name'] ?? 'Unknown';
                $n    = strtolower($name);
                $cnt  = $countOf("Member eq true AND Status eq 'Active' AND MembershipLevelId eq " . (int) $level['Id']);
                $levelBreakdown[] = ['name' => $name, 'count' => $cnt];
                if (str_contains($n, 'lifetime'))                                          $lifetime   += $cnt;
                elseif (str_contains($n, 'checkomatic') || str_contains($n, 'checkmatic')) $checkmatic += $cnt;
                else                                                                        $individual += $cnt;
            }

            // ── Zone + ZIP breakdown ──────────────────────────────────────────────
            // For each zone choice: count members (fast), then page through contacts
            // to collect ZIP distribution. ZIP data is only available on contact records.
            // This runs in the background command (portal:warm-dashboard), so time is fine.
            $zoneChoices = $this->getZoneChoices(); // [label => choiceId]
            $zoneMap     = []; // [zoneName => ['members'=>N, 'centers'=>[name=>['total'=>N,'zips'=>[zip=>N]]]]]
            $globalZipCount   = []; // [zip => count] across all zones
            $globalZipCity    = []; // [zip => city]

            // Maps WA choice label to [zoneName, centerName].
            // Actual WA prefixes from live account: NO=North, NW=Northwest,
            // SO=South, SW=Southwest, SE=Southeast.
            $parseZoneName = function (string $label): array {
                if (preg_match('/^([A-Z]+)\s*-\s*(.+)$/i', $label, $m)) {
                    $prefix = strtoupper(trim($m[1]));
                    return [
                        match ($prefix) {
                            'NO'      => 'North Zone',
                            'NW'      => 'Northwest Zone',
                            'SO'      => 'South Zone',
                            'SW'      => 'Southwest Zone',
                            'SE'      => 'Southeast Zone',
                            'N'       => 'North Zone',
                            'NE'      => 'Northeast Zone',
                            'S'       => 'South Zone',
                            'W'       => 'West Zone',
                            'E'       => 'East Zone',
                            default   => $prefix . ' Zone',
                        },
                        trim($m[2]),
                    ];
                }
                return [$label, $label];
            };

            foreach ($zoneChoices as $label => $choiceId) {
                // Active-only count — skip centers with no active members
                $centerActive = $countOf("Member eq true AND Status eq 'Active' AND 'Zone / Center' eq {$choiceId}");
                if ($centerActive === 0) continue;

                [$zoneName, $centerName] = $parseZoneName($label);

                $centerLapsed = $countOf("Member eq true AND Status eq 'Lapsed' AND 'Zone / Center' eq {$choiceId}");

                $centerIndividual     = 0;
                $centerCheckmatic     = 0;
                $centerLifetime       = 0;
                $centerLevelBreakdown = [];
                foreach ($this->getMembershipLevels() as $level) {
                    $name = $level['Name'] ?? 'Unknown';
                    $n    = strtolower($name);
                    $lcnt = $countOf("Member eq true AND Status eq 'Active' AND 'Zone / Center' eq {$choiceId} AND MembershipLevelId eq " . (int) $level['Id']);
                    $centerLevelBreakdown[] = ['name' => $name, 'count' => $lcnt];
                    if (str_contains($n, 'lifetime'))                                          $centerLifetime   += $lcnt;
                    elseif (str_contains($n, 'checkomatic') || str_contains($n, 'checkmatic')) $centerCheckmatic += $lcnt;
                    else                                                                        $centerIndividual += $lcnt;
                }

                $zoneMap[$zoneName] ??= ['members' => 0, 'centers' => []];
                $zoneMap[$zoneName]['members'] += $centerActive + $centerLapsed;
                $zoneMap[$zoneName]['centers'][$centerName] ??= [
                    'total' => 0, 'active' => 0, 'lapsed' => 0,
                    'individual' => 0, 'checkmatic' => 0, 'lifetime' => 0,
                    'level_breakdown' => [], 'zips' => [], 'zipCities' => [],
                ];
                $zoneMap[$zoneName]['centers'][$centerName]['total']          = $centerActive + $centerLapsed;
                $zoneMap[$zoneName]['centers'][$centerName]['active']         = $centerActive;
                $zoneMap[$zoneName]['centers'][$centerName]['lapsed']         = $centerLapsed;
                $zoneMap[$zoneName]['centers'][$centerName]['individual']      = $centerIndividual;
                $zoneMap[$zoneName]['centers'][$centerName]['checkmatic']      = $centerCheckmatic;
                $zoneMap[$zoneName]['centers'][$centerName]['lifetime']        = $centerLifetime;
                $zoneMap[$zoneName]['centers'][$centerName]['level_breakdown'] = $centerLevelBreakdown;

                // Page through active contacts only to collect ZIP distribution
                $skip     = 0;
                $pageSize = 100;
                do {
                    // Retry once on transient WA timeouts (cURL 28). Without this,
                    // a single slow page on one center kills the whole hourly sync.
                    $r = null;
                    for ($attempt = 1; $attempt <= 2; $attempt++) {
                        try {
                            $r = $this->apiGet(
                                "/accounts/{$accountId}/contacts?" . http_build_query([
                                    '$async'  => 'false',
                                    '$filter' => "Member eq true AND Status eq 'Active' AND 'Zone / Center' eq {$choiceId}",
                                    '$top'    => $pageSize,
                                    '$skip'   => $skip,
                                ])
                            );
                            break;
                        } catch (\Illuminate\Http\Client\ConnectionException $e) {
                            if ($attempt === 2) {
                                Log::warning('WA dashboard sync: paging timeout, skipping remainder of center', [
                                    'zone'   => $zoneName,
                                    'center' => $centerName,
                                    'skip'   => $skip,
                                    'error'  => $e->getMessage(),
                                ]);
                                $r = null;
                                break;
                            }
                            usleep(500_000);
                        }
                    }
                    if ($r === null || !$r->successful()) break;

                    $batch = $r->json()['Contacts'] ?? [];
                    if (empty($batch)) break;

                    foreach ($batch as $c) {
                        $zip  = '';
                        $city = '';
                        foreach ($c['FieldValues'] ?? [] as $fv) {
                            $code = $fv['SystemCode'] ?? '';
                            $val  = $fv['Value'] ?? '';
                            if ($code === 'custom-9967570') $zip  = trim((string) $val);
                            if ($code === 'custom-9967567') $city = trim((string) $val);
                        }
                        if ($zip) {
                            $zoneMap[$zoneName]['centers'][$centerName]['zips'][$zip] =
                                ($zoneMap[$zoneName]['centers'][$centerName]['zips'][$zip] ?? 0) + 1;
                            if ($city && ! isset($zoneMap[$zoneName]['centers'][$centerName]['zipCities'][$zip])) {
                                $zoneMap[$zoneName]['centers'][$centerName]['zipCities'][$zip] = $city;
                            }
                            $globalZipCount[$zip] = ($globalZipCount[$zip] ?? 0) + 1;
                            if ($city && !isset($globalZipCity[$zip])) $globalZipCity[$zip] = $city;
                        }
                    }
                    $skip += $pageSize;
                } while (count($batch) === $pageSize);
            }

        // ── Write to DB (truncate + re-insert for atomicity) ─────────────────
        DB::transaction(function () use (
            $total, $active, $lapsed, $individual, $checkmatic, $lifetime,
            $levelBreakdown, $zoneMap, $globalZipCount
        ) {
            $statData = [
                'total_members'      => $total,
                'active_members'     => $active,
                'lapsed_members'     => $lapsed,
                'individual_members' => $individual,
                'checkmatic_members' => $checkmatic,
                'lifetime_members'   => $lifetime,
                'level_breakdown'    => json_encode($levelBreakdown),
                'total_zips'         => count($globalZipCount),
                'last_synced_at'     => now(),
            ];
            // Stats — update existing row or create if first run
            \App\Models\DashboardStat::query()->update($statData)
                || \App\Models\DashboardStat::create($statData);

            // Delete child rows first to respect the FK constraint, then parent
            \App\Models\DashboardCenterZip::query()->delete();
            \App\Models\DashboardCenter::query()->delete();

            foreach ($zoneMap as $zoneName => $zoneData) {
                foreach ($zoneData['centers'] as $centerName => $centerData) {
                    $center = \App\Models\DashboardCenter::create([
                        'zone_name'          => $zoneName,
                        'center_name'        => $centerName,
                        'member_count'       => $centerData['total'],
                        'active_members'     => $centerData['active']           ?? 0,
                        'lapsed_members'     => $centerData['lapsed']           ?? 0,
                        'individual_members' => $centerData['individual']       ?? 0,
                        'checkmatic_members' => $centerData['checkmatic']       ?? 0,
                        'lifetime_members'   => $centerData['lifetime']         ?? 0,
                        'level_breakdown'    => json_encode($centerData['level_breakdown'] ?? []),
                    ]);

                    $zipRows = [];
                    foreach ($centerData['zips'] as $zip => $count) {
                        $zipRows[] = [
                            'dashboard_center_id' => $center->id,
                            'zip'                 => $zip,
                            'city'                => $centerData['zipCities'][$zip] ?? '',
                            'member_count'        => $count,
                        ];
                    }
                    if ($zipRows) {
                        \App\Models\DashboardCenterZip::insert($zipRows);
                    }
                }
            }
        });
    }

    /**
     * Fetches contacts from WA with optional filters, returning a paginated result.
     * Returns ['items' => [...], 'total' => int]
     */
    public function getMembersPage(int $page, int $perPage, array $filters = []): array
    {
        $accountId = $this->getAccountId();
        $skip      = ($page - 1) * $perPage;

        // WA treats $count=true as a count-only mode — returns {"Count": N} with no Contacts.
        // Two calls: one for total count, one for the page.
        //
        // 'Member eq true' as the base filter is required: without it the list endpoint
        // returns only ~8 restricted FieldValues per contact. With it, all 62 FieldValues
        // including custom fields (Zone, ZIP, City, Street, MemberSince, RenewalDue) are returned.
        $baseParams  = ['$async' => 'false'];
        $filterParts = ['Member eq true'];

        // Search — limited to Name and Email only. WA's simpleQuery also matches
        // phone/ID/company, so it's replaced with an explicit OData $filter on
        // the top-level FirstName/LastName/Email fields. (Street Address is a
        // FieldValue and cannot be filtered server-side — see the ZIP note below.)
        //
        // The user typically types the full name they see in the table
        // ("Shaheer Zaeem"). Matching that as a single substring against any
        // one of FirstName / LastName / Email would always fail, since no
        // single field contains both tokens. So split the input on whitespace
        // and require EVERY token to match at least one of the three fields —
        // "Shaheer Zaeem" then matches because FirstName contains "Shaheer"
        // AND LastName contains "Zaeem". A single-token query keeps working
        // exactly as before.
        if (!empty($filters['search'])) {
            $raw    = trim((string) $filters['search']);
            $tokens = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokenFilters = [];
            foreach ($tokens as $token) {
                $t = str_replace("'", "''", $token);
                // WA's OData dialect: substringof(FieldName, 'literal').
                // The standard (literal, field) order makes WA treat the
                // literal as a field reference and 400 with
                // "Unknown field 'Shaheer'." Confirmed by laravel.log entries.
                $tokenFilters[] = "(substringof(FirstName, '{$t}')"
                    . " OR substringof(LastName, '{$t}')"
                    . " OR substringof(Email, '{$t}'))";
            }
            if ($tokenFilters) {
                $filterParts[] = count($tokenFilters) === 1
                    ? $tokenFilters[0]
                    : '(' . implode(' AND ', $tokenFilters) . ')';
            }
        }

        // Status filter — WA values: Active, Lapsed, PendingNew, PendingRenewal, Archived
        if (!empty($filters['status'])) {
            $st = ucfirst(strtolower($filters['status']));
            if ($st === 'Expired') $st = 'Lapsed';
            $filterParts[] = "Status eq '{$st}'";
        }

        // Zone / Center filter — maps DB zone/center values to WA choice IDs.
        // WA choice labels (from live account): NO- = North, NW- = Northwest,
        // SO- = South, SW- = Southwest, SE- = Southeast.
        // Flat map [center => choiceId] for center-only lookups (no zone given).
        if (!empty($filters['zone']) || !empty($filters['center'])) {
            $centerMap = [
                'North' => [
                    '*'            => [10714781, 10714782, 10714783, 10714784],
                    'Adel Road'    => [10714781],
                    'Champions'    => [10714782],
                    'Woodlands'    => [10714783],
                    'Cypress'      => [10714784],
                ],
                'Northwest' => [
                    '*'            => [10714785, 10714786, 10714787],
                    'Bear Creek'   => [10714785],
                    'Katy'         => [10714786],
                    'Spring Branch'=> [10714787],
                ],
                'Southeast' => [
                    '*'            => [10714788, 10714790],
                    'HWY3'         => [10714788],
                    'Pearland'     => [10714790],
                ],
                'South' => [
                    '*'            => [10714792, 21342681],
                    'Brand Lane'   => [10714792],
                    'Ayesha'       => [21342681],
                ],
                'Southwest' => [
                    '*'            => [10714794, 10714795, 10714796, 10714797],
                    'River Oaks'   => [10714794],
                    'Synott'       => [10714795],
                    'Mission Bend' => [10714796],
                    'New Territory'=> [10714797],
                ],
            ];

            // Flat center → choiceId map for when no zone is specified
            $flatCenterMap = [
                'Adel Road'    => [10714781],
                'Champions'    => [10714782],
                'Woodlands'    => [10714783],
                'Cypress'      => [10714784],
                'Bear Creek'   => [10714785],
                'Katy'         => [10714786],
                'Spring Branch'=> [10714787],
                'HWY3'         => [10714788],
                'Pearland'     => [10714790],
                'Brand Lane'   => [10714792],
                'Ayesha'       => [21342681],
                'River Oaks'   => [10714794],
                'Synott'       => [10714795],
                'Mission Bend' => [10714796],
                'New Territory'=> [10714797],
            ];

            $wantedZone   = ucfirst(strtolower($filters['zone']   ?? ''));
            $wantedCenter = $filters['center'] ?? '';

            $matchedIds = [];
            if ($wantedZone && isset($centerMap[$wantedZone])) {
                $zoneEntry  = $centerMap[$wantedZone];
                $matchedIds = ($wantedCenter && isset($zoneEntry[$wantedCenter]))
                    ? $zoneEntry[$wantedCenter]
                    : $zoneEntry['*'];
            } elseif ($wantedCenter && isset($flatCenterMap[$wantedCenter])) {
                // center filter only (city-wide user picks a masjid without a zone)
                $matchedIds = $flatCenterMap[$wantedCenter];
            }

            if ($matchedIds) {
                if (count($matchedIds) === 1) {
                    $filterParts[] = "'Zone / Center' eq {$matchedIds[0]}";
                } else {
                    $orParts = array_map(fn($id) => "'Zone / Center' eq {$id}", $matchedIds);
                    $filterParts[] = '(' . implode(' OR ', $orParts) . ')';
                }
            }
        }

        // Level filter — exact level name match against cached WA levels
        if (!empty($filters['level'])) {
            $wantedLevel = $filters['level'];
            foreach ($this->getMembershipLevels() as $lvl) {
                if (strcasecmp($lvl['Name'] ?? '', $wantedLevel) === 0) {
                    $filterParts[] = 'MembershipLevelId eq ' . (int) $lvl['Id'];
                    break;
                }
            }
        }

        $baseParams['$filter'] = implode(' AND ', $filterParts);

        // ZIP sanitization — the raw filter can arrive with junk like "TX 77084"
        // (DB has dirty values with NBSPs and state prefixes). The ZIP-filtered
        // path below crawls every contact in WA looking for a match, so a
        // value that can never match would trigger the worst-case fetch-all
        // and very likely time out PHP. Extract a clean 5-digit ZIP; if the
        // input contains no 5-digit run, drop the filter entirely.
        $rawZip    = (string) ($filters['zip'] ?? '');
        $wantedZip = preg_match('/(\d{5})/', $rawZip, $m) ? $m[1] : '';
        if ($rawZip !== '' && $wantedZip === '') {
            Log::warning('WA getMembersPage: dropped unparseable zip filter', ['raw' => $rawZip]);
        }

        // ── ZIP-filtered path ─────────────────────────────────────────────────
        // ZIP lives inside FieldValues, which WA's OData cannot filter on, so
        // we cannot ask WA for "page N of members with ZIP=X". Instead:
        //
        //   1. Fetch the full OData-matching set (non-ZIP filters applied
        //      server-side) and cache it for 10 minutes, keyed by the
        //      OData-filter signature. This makes a request a one-time
        //      WA cost; every subsequent page click of the same filter set
        //      paginates against the cache without re-hitting WA.
        //   2. Apply the ZIP match in PHP against the cached rows.
        //   3. Paginate the matched rows here. This IS the server-side
        //      pagination — Laravel returns only the requested $top/$skip
        //      window to the client.
        //
        // The cache key includes the OData $filter so different filter
        // combinations get independent caches.
        if ($wantedZip !== '') {
            // Tie this pool cache to the global members_cache_version so the
            // dashboard warm command (and any future invalidation hook)
            // instantly invalidates it alongside the per-user page caches.
            $version  = (int) Cache::get('members_cache_version', 1);
            $cacheKey = 'wa_zip_pool_v' . $version . '_' . md5($baseParams['$filter']);
            $rows = Cache::remember($cacheKey, 600, function () use ($accountId, $baseParams) {
                $all  = $this->fetchAllContacts($accountId, $baseParams);
                $rows = array_map(fn($c) => $this->mapContactToMemberRow($c), $all);
                // Sort once at cache time so every page click is cheap.
                usort($rows, function (array $a, array $b) {
                    return [$a['zip'], $a['address'], $a['name']]
                       <=> [$b['zip'], $b['address'], $b['name']];
                });
                return $rows;
            });

            // Apply the ZIP match.
            $rows = array_values(array_filter(
                $rows,
                fn(array $row) => trim((string) ($row['zip'] ?? '')) === $wantedZip
            ));

            // Server-side paginate the matched rows: return only the
            // requested window plus the true total for the paginator.
            $total = count($rows);
            $items = array_slice($rows, $skip, $perPage);

            return ['items' => $items, 'total' => $total];
        }

        // ── Unfiltered path ───────────────────────────────────────────────────
        // No ZIP filter: WA does the paging, so fetch only the requested page.

        // 1. Get total count
        $countParams = array_merge($baseParams, ['$count' => 'true']);
        $cr = $this->apiGet("/accounts/{$accountId}/contacts?" . http_build_query($countParams));
        $total = $cr->successful() ? (int) ($cr->json()['Count'] ?? 0) : 0;

        // 2. Get this page of contacts (no $select — omitting it returns all FieldValues)
        $pageParams = array_merge($baseParams, [
            '$top'  => $perPage,
            '$skip' => $skip,
        ]);

        $r = $this->apiGet("/accounts/{$accountId}/contacts?" . http_build_query($pageParams));

        if (!$r->successful()) {
            Log::error('WA getMembersPage failed', ['status' => $r->status(), 'body' => $r->body()]);
            return ['items' => [], 'total' => $total];
        }

        $body     = $r->json();
        $contacts = $body['Contacts'] ?? (is_array($body) ? $body : []);

        if ($total === 0) {
            $total = count($contacts);
        }

        $items = array_map(fn($c) => $this->mapContactToMemberRow($c), $contacts);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Fetch every contact matching the given base params, paging through the
     * WA contacts endpoint until exhausted. Used by the ZIP-filtered members
     * listing, where filtering must happen across the full result set rather
     * than a single page. A hard page cap guards against runaway loops.
     *
     * @return array<int,array> raw WA contact arrays
     */
    private function fetchAllContacts(string $accountId, array $baseParams): array
    {
        $all       = [];
        $pageSize  = 100;          // WA's max page size for the contacts endpoint
        $maxPages  = 200;          // safety cap → up to 20,000 contacts
        $skip      = 0;
        // Wall-clock budget so a slow WA can never let the loop run long enough
        // to hit PHP's max_execution_time. 45s leaves headroom for the rest of
        // the request to render even when we abort partway through.
        $deadline  = microtime(true) + 45.0;

        for ($i = 0; $i < $maxPages; $i++) {
            if (microtime(true) > $deadline) {
                Log::warning('WA fetchAllContacts: time budget exhausted, returning partial result', [
                    'fetched' => count($all), 'skip' => $skip,
                ]);
                break;
            }

            $params = array_merge($baseParams, ['$top' => $pageSize, '$skip' => $skip]);
            $r = $this->apiGet("/accounts/{$accountId}/contacts?" . http_build_query($params));

            if (!$r->successful()) {
                Log::error('WA fetchAllContacts page failed', [
                    'skip' => $skip, 'status' => $r->status(),
                ]);
                break;
            }

            $body  = $r->json();
            $batch = $body['Contacts'] ?? (is_array($body) ? $body : []);
            if (empty($batch)) {
                break;
            }

            $all  = array_merge($all, $batch);
            $skip += $pageSize;

            // Last page reached when the batch is short of a full page.
            if (count($batch) < $pageSize) {
                break;
            }
        }

        return $all;
    }

    /**
     * Maps a WA contact array to the flat array shape the members view expects.
     * MemberSince and RenewalDue live inside FieldValues (SystemCode MemberSince / RenewalDue),
     * not at the top level of the contact object.
     */
    private function mapContactToMemberRow(array $c): array
    {
        $firstName = $c['FirstName'] ?? '';
        $lastName  = $c['LastName']  ?? '';
        $name      = trim("$firstName $lastName") ?: '—';

        $levelName = $c['MembershipLevel']['Name'] ?? '—';

        $zone      = '';
        $zip       = '';
        $city      = '';
        $street    = '';
        $joined    = null;
        $renewal   = null;

        foreach ($c['FieldValues'] ?? [] as $fv) {
            $code  = $fv['SystemCode'] ?? '';
            $fname = $fv['FieldName']  ?? '';
            $val   = $fv['Value']      ?? '';

            // Zone / Center (choice field — value is {"Id":…,"Label":"…"})
            if ($code === 'custom-9967573' || $fname === 'Zone / Center') {
                $zone = is_array($val) ? ($val['Label'] ?? '') : (string) $val;
            }
            // ZIP
            if ($code === 'custom-9967570' || $fname === 'ZIP') {
                $zip = trim((string) $val);
            }
            // City
            if ($code === 'custom-9967567' || $fname === 'City') {
                $city = trim((string) $val);
            }
            // Street Address
            if ($code === 'custom-9967566' || $fname === 'Street Address') {
                $street = trim((string) $val);
            }
            // MemberSince — stored in FieldValues, value may be null or ISO date string
            if ($code === 'MemberSince' || $fname === 'Member since') {
                $joined = $val ?: null;
            }
            // RenewalDue — stored in FieldValues
            if ($code === 'RenewalDue' || $fname === 'Renewal due') {
                $renewal = $val ?: null;
            }
        }

        $address = trim(implode(', ', array_filter([$street, $city]))) ?: '—';

        $fmtDate = function (mixed $d): string {
            if (!$d || $d === 'null') return '—';
            $s = is_array($d) ? ($d['Value'] ?? '') : (string) $d;
            if (!$s || $s === 'Never') return $s ?: '—';
            try { return Carbon::parse($s)->format('m-d-Y'); } catch (\Throwable) { return $s; }
        };

        return [
            'name'     => $name,
            'type'     => $levelName,
            'type_sub' => '',
            'zone'     => $zone ?: '—',
            'masjid'   => '',
            'address'  => $address,
            'zip'      => $zip ?: '—',
            'joined'   => $fmtDate($joined),
            'renewal'  => $fmtDate($renewal),
            'status'   => $c['Status'] ?? 'Active',
        ];
    }


    private function calcRenewalDate(string $type, int $years = 1): string
    {
        $years = max(1, $years);

        return match(true) {
            str_contains($type, 'lifetime')    => 'Never',
            str_contains($type, 'checkomatic') => now()->addMonth()->toIso8601String(),
            default                            => now()->addYears($years - 1)->endOfYear()->toIso8601String(),
        };
    }

    // ─── MEMBER PORTAL — INVOICES ───────────────────────────────────────────
    // GET /accounts/{accountId}/invoices?contactId={contactId}
    // Returns the contact's invoices, or [] on failure.

    public function getInvoicesForContact(int $contactId): array
    {
        try {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/invoices?" . http_build_query([
                'contactId' => $contactId,
                'idsOnly'   => 'false',
            ]));
            if (! $r->successful()) {
                Log::warning('WA getInvoicesForContact: API error', ['status' => $r->status(), 'contact_id' => $contactId]);
                return [];
            }
            $body = $r->json();
            return $body['Invoices'] ?? (is_array($body) ? array_values(array_filter($body, 'is_array')) : []);
        } catch (\Throwable $e) {
            Log::error('WA getInvoicesForContact exception', ['contact_id' => $contactId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── MEMBER PORTAL — SINGLE INVOICE ─────────────────────────────────────
    // GET /accounts/{accountId}/invoices/{invoiceId}
    // Returns the full invoice document, or null on failure.

    public function getInvoiceById(int $invoiceId): ?array
    {
        try {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/invoices/{$invoiceId}");
            if (! $r->successful()) {
                Log::warning('WA getInvoiceById: API error', ['status' => $r->status(), 'invoice_id' => $invoiceId]);
                return null;
            }
            $body = $r->json();
            return is_array($body) ? $body : null;
        } catch (\Throwable $e) {
            Log::error('WA getInvoiceById exception', ['invoice_id' => $invoiceId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ─── MEMBER PORTAL — PAYMENTS ───────────────────────────────────────────
    // GET /accounts/{accountId}/payments?contactId={contactId}
    // Returns the contact's payments, or [] on failure.

    public function getPaymentsForContact(int $contactId): array
    {
        try {
            $accountId = $this->getAccountId();
            $r = $this->apiGet("/accounts/{$accountId}/payments?" . http_build_query([
                'contactId' => $contactId,
            ]));
            if (! $r->successful()) {
                Log::warning('WA getPaymentsForContact: API error', ['status' => $r->status(), 'contact_id' => $contactId]);
                return [];
            }
            $body = $r->json();
            return $body['Payments'] ?? (is_array($body) ? array_values(array_filter($body, 'is_array')) : []);
        } catch (\Throwable $e) {
            Log::error('WA getPaymentsForContact exception', ['contact_id' => $contactId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── MEMBER PORTAL — FAMILY MEMBERS ─────────────────────────────────────
    // Finds related contacts that store this primary's contact ID in the
    // custom "Member Identifier" field. Returns [] if none / on failure.

    public function getFamilyMembers(int $primaryContactId): array
    {
        try {
            $accountId  = $this->getAccountId();
            $systemCode = $this->getFieldSystemCode('Member Identifier');
            if (! $systemCode) {
                Log::warning('WA getFamilyMembers: Member Identifier field not found');
                return [];
            }

            $r = $this->apiGet("/accounts/{$accountId}/contacts?" . http_build_query([
                '$filter' => "'{$systemCode}' eq '{$primaryContactId}'",
                '$async'  => 'false',
                '$top'    => 50,
            ]));
            if (! $r->successful()) {
                Log::warning('WA getFamilyMembers: API error', ['status' => $r->status(), 'primary' => $primaryContactId]);
                return [];
            }
            $body     = $r->json();
            $contacts = $body['Contacts'] ?? (is_array($body) ? array_values(array_filter($body, 'is_array')) : []);

            // Exclude the primary contact itself if it matches the filter.
            return array_values(array_filter($contacts, fn ($c) => ($c['Id'] ?? null) !== $primaryContactId));
        } catch (\Throwable $e) {
            Log::error('WA getFamilyMembers exception', ['primary' => $primaryContactId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    private function apiGet(string $path): \Illuminate\Http\Client\Response
    {
        // Hard timeouts so a stalled WA call can never burn PHP's whole
        // max_execution_time. 4s connect, 20s total response — enough for
        // larger paged contact requests but bounded.
        return Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->connectTimeout(4)
            ->timeout(20)
            ->get($this->baseUrl . $path);
    }

    private function apiPost(string $path, array $data): \Illuminate\Http\Client\Response
    {
        Log::debug('WA apiPost', ['path' => $path, 'payload' => $data]);
        return Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(4)
            ->timeout(20)
            ->post($this->baseUrl . $path, $data);
    }

    private function apiPut(string $path, array $data): \Illuminate\Http\Client\Response
    {
        Log::debug('WA apiPut', ['path' => $path, 'payload' => $data]);
        return Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(4)
            ->timeout(20)
            ->put($this->baseUrl . $path, $data);
    }
}

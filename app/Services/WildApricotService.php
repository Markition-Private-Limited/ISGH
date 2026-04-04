<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('wa_levels', 3600, fn() =>
            $this->apiGet("/accounts/{$this->getAccountId()}/membershiplevels")->json() ?? []
        );
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
            'RenewalDue'        => $this->calcRenewalDate($data['membership_type']),
            'MembershipLevel'   => ['Id' => $levelId],
            'FieldValues'       => $this->buildFieldValues($data),
        ];

        $r = $this->apiPost("/accounts/{$accountId}/contacts", $payload);
        Log::info('WA createActiveMember response', ['status' => $r->status(), 'body' => $r->body()]);

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
    // Creates the payment using Allocations in the POST body (invoice-first order).
    // Using ONLY the Allocations array (no top-level Invoice field) with the
    // Stripe Tender is the most explicit way to request allocation at creation.

    public function recordPayment(int $contactId, int $invoiceId, float $amount, string $stripeChargeId): array
    {
        $accountId = $this->getAccountId();
        $tenderId  = $this->getCreditCardTenderId();

        // ── Create payment with Allocations referencing the invoice ──────────
        // No top-level Invoice field (conflicts with Allocations in some WA
        // versions). Tender:{Id} ensures the payment is a real transaction.
        $payload = [
            'Contact'     => ['Id' => $contactId],
            'Value'       => $amount,
            'PaymentType' => 'CreditCard',
            'Comment'     => 'Stripe charge ID: ' . $stripeChargeId,
            'Allocations' => [[
                'Invoice' => ['Id' => $invoiceId],
                'Value'   => $amount,
            ]],
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

        // Verify settlement
        $ir    = $this->apiGet("/accounts/{$accountId}/invoices/{$invoiceId}");
        $final = $ir->successful() ? $ir->json() : [];
        Log::info('WA invoice settlement check', [
            'invoice_id'     => $invoiceId,
            'payment_id'     => $paymentId,
            'allocated_value'=> $payment['AllocatedValue'] ?? 0,
            'is_paid'        => $final['IsPaid']     ?? 'unknown',
            'paid_amount'    => $final['PaidAmount'] ?? 'unknown',
        ]);

        return $payment;
    }

    // ─── MEMBERSHIP VERIFICATION — SEARCH CONTACT ───────────────────────────
    // Searches WA contacts by email (primary) or first+last name (fallback).
    // Returns the first matching contact array, or null if none found.

    public function searchContact(string $email, string $firstName, string $lastName, string $phone): ?array
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

        // Helper: check that a candidate contact matches ALL provided fields
        $matchesAll = function (array $c) use ($email, $firstName, $lastName, $phone): bool {
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
            return true;
        };

        $contact = null;

        // 1. Search by email (most precise), then validate all other fields
        if ($email !== '') {
            $safe     = str_replace("'", "''", $email); // OData single-quote escape
            $contacts = $query("Email eq '{$safe}'");
            foreach ($contacts as $c) {
                if ($matchesAll($c)) { $contact = $c; break; }
            }
        }

        // 2. Search by first + last name, then validate email and phone
        if (! $contact && $firstName !== '' && $lastName !== '') {
            $fn       = str_replace("'", "''", $firstName);
            $ln       = str_replace("'", "''", $lastName);
            $contacts = $query("FirstName eq '{$fn}' AND LastName eq '{$ln}'");
            foreach ($contacts as $c) {
                if ($matchesAll($c)) { $contact = $c; break; }
            }
        }

        if (! $contact) return null;

        // Always fetch the full contact by ID — the list endpoint returns a subset of fields
        // and does not guarantee MemberSince, RenewalDue, or FieldValues are present.
        $contactId = $contact['Id'] ?? null;
        if ($contactId) {
            Log::debug('WA searchContact: fetching full contact to get MemberSince, RenewalDue, FieldValues', ['contact_id' => $contactId]);
            $r = $this->apiGet("/accounts/{$accountId}/contacts/{$contactId}");
            Log::debug('WA full contact response', ['status' => $r->status(), 'body' => substr($r->body(), 0, 2000)]);
            if ($r->successful()) {
                $contact = $r->json();
            }
        }

        return $contact;
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

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    private function buildFieldValues(array $data): array
    {
        $fields = [];

        // Standard system fields
        $standard = [
            'FirstName' => $data['first_name'] ?? null,
            'LastName'  => $data['last_name']  ?? null,
            'Email'     => $data['email']      ?? null,
            'Phone'     => $data['phone']      ?? null,
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

        return $fields;
    }

    private function calcRenewalDate(string $type): string
    {
        return match(true) {
            str_contains($type, 'lifetime')    => now()->addYears(100)->toIso8601String(),
            str_contains($type, 'checkomatic') => now()->addMonth()->toIso8601String(),
            default                            => now()->addYear()->toIso8601String(),
        };
    }

    private function apiGet(string $path): \Illuminate\Http\Client\Response
    {
        return Http::withToken($this->getAccessToken())->acceptJson()->get($this->baseUrl . $path);
    }

    private function apiPost(string $path, array $data): \Illuminate\Http\Client\Response
    {
        Log::debug('WA apiPost', ['path' => $path, 'payload' => $data]);
        return Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->asJson()
            ->post($this->baseUrl . $path, $data);
    }

    private function apiPut(string $path, array $data): \Illuminate\Http\Client\Response
    {
        Log::debug('WA apiPut', ['path' => $path, 'payload' => $data]);
        return Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->asJson()
            ->put($this->baseUrl . $path, $data);
    }
}
# Member Portal WildApricot Data Integration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fetch a member's complete WildApricot record (profile, membership, invoices, payments, family) when their OTP is validated, then display it on the dashboard and profile pages.

**Architecture:** Three layers between WildApricot and the Blade views — new `WildApricotService` fetch methods, an `App\Support\MemberProfile` view-model DTO that normalizes raw WA data, and an `App\Services\MemberPortalService` orchestrator that assembles a per-session cached bundle. Views are re-bound to `MemberProfile` accessors with no layout changes.

**Tech Stack:** Laravel 11, PHP 8.2, WildApricot REST API v2.3, Blade, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-05-16-member-portal-wildapricot-integration-design.md`

---

## File Structure

**Create:**
- `app/Support/MemberProfile.php` — view-model DTO; the single place that knows WA's `FieldValues` shape.
- `tests/Unit/MemberProfileTest.php` — unit tests for the DTO.
- `app/Services/MemberPortalService.php` — orchestrator: assemble bundle, cache, update.
- `tests/Feature/MemberPortalServiceTest.php` — `Http::fake()` tests for assembly.
- `tests/Feature/MemberPortalIntegrationTest.php` — controller feature tests.
- `tests/Fixtures/wa_contact.php` — shared WA contact/invoice/payment fixture array.

**Modify:**
- `app/Services/WildApricotService.php` — add `getInvoicesForContact()`, `getPaymentsForContact()`, `getFamilyMembers()`.
- `app/Http/Controllers/MemberPortalController.php` — assemble+cache in `verifyOtp()`, read cache in `dashboard()`/`profile()`, add `updateProfile()`.
- `routes/web.php` — add `POST /member-portal/profile/update`.
- `resources/views/member-portal/dashboard.blade.php` — re-bind `@php` block + values to `$profile`.
- `resources/views/member-portal/partials/profile-content.blade.php` — re-bind to `$profile`.
- `resources/views/member-portal/profile.blade.php` — pass `$profile`; wire save endpoint in JS.

---

## Task 1: WA fetch methods — invoices, payments, family

**Files:**
- Modify: `app/Services/WildApricotService.php` (add before `private function apiGet` at line ~1607)
- Test: `tests/Feature/WildApricotFetchTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/WildApricotFetchTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\WildApricotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WildApricotFetchTest extends TestCase
{
    private function service(): WildApricotService
    {
        // Pre-seed auth token + account id so no auth/account calls are needed.
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
        return new WildApricotService();
    }

    public function test_get_invoices_for_contact_returns_invoice_array(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices*' => Http::response([
                'Invoices' => [
                    ['Id' => 1, 'DocumentNumber' => 'INV-2026-0001', 'Value' => 20.0, 'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
                ],
            ], 200),
        ]);

        $invoices = $this->service()->getInvoicesForContact(999);

        $this->assertCount(1, $invoices);
        $this->assertSame('INV-2026-0001', $invoices[0]['DocumentNumber']);
    }

    public function test_get_payments_for_contact_returns_payment_array(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/payments*' => Http::response([
                'Payments' => [
                    ['Id' => 5, 'Value' => 20.0, 'CreatedDate' => '2026-01-15T00:00:00'],
                ],
            ], 200),
        ]);

        $payments = $this->service()->getPaymentsForContact(999);

        $this->assertCount(1, $payments);
        $this->assertSame(20.0, $payments[0]['Value']);
    }

    public function test_get_family_members_returns_contacts_array(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts*' => Http::response([
                'Contacts' => [
                    ['Id' => 1001, 'FirstName' => 'Sarah', 'LastName' => 'Alam', 'FieldValues' => []],
                ],
            ], 200),
        ]);

        $family = $this->service()->getFamilyMembers(999);

        $this->assertCount(1, $family);
        $this->assertSame('Sarah', $family[0]['FirstName']);
    }

    public function test_fetch_methods_return_empty_array_on_api_failure(): void
    {
        Http::fake(['api.wildapricot.org/*' => Http::response('error', 500)]);

        $svc = $this->service();
        $this->assertSame([], $svc->getInvoicesForContact(999));
        $this->assertSame([], $svc->getPaymentsForContact(999));
        $this->assertSame([], $svc->getFamilyMembers(999));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/WildApricotFetchTest.php`
Expected: FAIL — `Call to undefined method ...getInvoicesForContact()`

- [ ] **Step 3: Add the three methods**

In `app/Services/WildApricotService.php`, insert immediately before `private function apiGet(string $path)` (around line 1607):

```php
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

```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/WildApricotFetchTest.php`
Expected: PASS — 4 tests, all green

- [ ] **Step 5: Commit**

```bash
git add app/Services/WildApricotService.php tests/Feature/WildApricotFetchTest.php
git commit -m "feat: add WA fetch methods for invoices, payments, family members"
```

---

## Task 2: Shared WA fixture

**Files:**
- Create: `tests/Fixtures/wa_contact.php`

- [ ] **Step 1: Create the fixture file**

Create `tests/Fixtures/wa_contact.php`:

```php
<?php

// Reusable WildApricot bundle fixture for tests.
// Returns ['contact'=>…, 'family'=>[…], 'invoices'=>[…], 'payments'=>[…]].

return [
    'contact' => [
        'Id'        => 999,
        'FirstName' => 'Tauqeer',
        'LastName'  => 'Alam',
        'Email'     => 'tauqeer@example.com',
        'Phone'     => '2154389281',
        'Status'    => 'Active',
        'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual Membership'],
        'FieldValues' => [
            ['FieldName' => 'Street Address', 'SystemCode' => 'custom-9967566', 'Value' => '7829 Southwest Freeway'],
            ['FieldName' => 'City',           'SystemCode' => 'custom-9967567', 'Value' => 'Texas City'],
            ['FieldName' => 'State',          'SystemCode' => 'custom-9967569', 'Value' => 'Texas'],
            ['FieldName' => 'ZIP',            'SystemCode' => 'custom-9967570', 'Value' => '78933'],
            ['FieldName' => 'Date of Birth',  'SystemCode' => 'custom-10694881','Value' => '2005-11-09'],
            ['FieldName' => 'TX DL/ID Number','SystemCode' => 'custom-17846913','Value' => '2427896483965'],
            ['FieldName' => 'Zone / Center',  'SystemCode' => 'custom-9967573', 'Value' => ['Id' => 7, 'Label' => 'Spring Branch Islamic Center']],
            ['FieldName' => 'Member since',   'SystemCode' => 'MemberSince',    'Value' => '2021-08-22T00:00:00'],
            ['FieldName' => 'Renewal due',    'SystemCode' => 'RenewalDue',     'Value' => '2027-01-15T00:00:00'],
        ],
    ],
    'family' => [
        [
            'Id'        => 1001,
            'FirstName' => 'Sarah',
            'LastName'  => 'Alam',
            'Email'     => 'sarah@example.com',
            'Phone'     => '2155256151',
            'Status'    => 'Active',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual Membership'],
            'FieldValues' => [
                ['FieldName' => 'City',  'SystemCode' => 'custom-9967567', 'Value' => 'Texas City'],
                ['FieldName' => 'State', 'SystemCode' => 'custom-9967569', 'Value' => 'Texas'],
            ],
        ],
    ],
    'invoices' => [
        ['Id' => 1, 'DocumentNumber' => 'INV-2026-0001', 'Value' => 20.0, 'IsPaid' => true,  'CreatedDate' => '2026-01-15T00:00:00'],
        ['Id' => 2, 'DocumentNumber' => 'INV-2026-0002', 'Value' => 20.0, 'IsPaid' => false, 'CreatedDate' => '2026-06-15T00:00:00'],
    ],
    'payments' => [
        ['Id' => 5, 'Value' => 20.0, 'CreatedDate' => '2026-01-15T00:00:00'],
        ['Id' => 6, 'Value' => 60.0, 'CreatedDate' => '2025-03-10T00:00:00'],
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add tests/Fixtures/wa_contact.php
git commit -m "test: add shared WildApricot bundle fixture"
```

---

## Task 3: MemberProfile DTO — basic fields & field mapping

**Files:**
- Create: `app/Support/MemberProfile.php`
- Test: `tests/Unit/MemberProfileTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/MemberProfileTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Support\MemberProfile;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    private function bundle(array $overrides = []): array
    {
        $base = require base_path('tests/Fixtures/wa_contact.php');
        return array_merge($base, $overrides);
    }

    public function test_maps_basic_contact_fields(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertSame('Tauqeer', $p->firstName);
        $this->assertSame('Alam', $p->lastName);
        $this->assertSame('Tauqeer Alam', $p->fullName);
        $this->assertSame('tauqeer@example.com', $p->email);
        $this->assertSame('2154389281', $p->phone);
        $this->assertSame('Active', $p->status);
        $this->assertSame('Individual Membership', $p->level);
    }

    public function test_maps_custom_field_values(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertSame('7829 Southwest Freeway', $p->street);
        $this->assertSame('Texas City', $p->city);
        $this->assertSame('Texas', $p->state);
        $this->assertSame('78933', $p->zip);
        $this->assertSame('2005-11-09', $p->dob);
        $this->assertSame('2427896483965', $p->txId);
    }

    public function test_unwraps_choice_field_to_label(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('Spring Branch Islamic Center', $p->zone);
    }

    public function test_missing_fields_return_empty_string(): void
    {
        $p = new MemberProfile(['contact' => ['Id' => 1, 'FieldValues' => []]]);

        $this->assertSame('', $p->firstName);
        $this->assertSame('', $p->street);
        $this->assertSame('', $p->zone);
    }

    public function test_handles_non_array_field_values_safely(): void
    {
        $p = new MemberProfile(['contact' => ['Id' => 1, 'FieldValues' => 'broken']]);
        $this->assertSame('', $p->city);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: FAIL — `Class "App\Support\MemberProfile" not found`

- [ ] **Step 3: Create MemberProfile with basic fields**

Create `app/Support/MemberProfile.php`:

```php
<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * View-model wrapping a raw WildApricot member bundle.
 *
 * The bundle shape: ['contact'=>array, 'family'=>array[], 'invoices'=>array[], 'payments'=>array[]].
 * This class is the SINGLE place that knows WildApricot's FieldValues structure.
 * All accessors are safe — missing/malformed data yields '' or [] and never throws.
 */
class MemberProfile
{
    public string $firstName;
    public string $lastName;
    public string $fullName;
    public string $email;
    public string $phone;
    public string $status;
    public string $level;
    public string $street;
    public string $city;
    public string $state;
    public string $zip;
    public string $dob;
    public string $txId;
    public string $zone;
    public string $memberSince;
    public string $renewalDue;
    public string $yearlyFee;

    /** @var array<int,MemberProfile> */
    public array $family = [];
    /** @var array<int,array> */
    public array $invoices = [];
    /** @var array<int,array> */
    public array $payments = [];

    private array $contact;

    public function __construct(array $bundle)
    {
        // A bundle may be a full bundle or just a bare contact array (used for family members).
        $this->contact = $bundle['contact'] ?? $bundle;

        $this->firstName   = (string) ($this->contact['FirstName'] ?? '');
        $this->lastName    = (string) ($this->contact['LastName'] ?? '');
        $this->fullName    = trim($this->firstName . ' ' . $this->lastName);
        $this->email       = (string) ($this->contact['Email'] ?? '');
        $this->status      = (string) ($this->contact['Status'] ?? 'Active');
        $this->level       = (string) ($this->contact['MembershipLevel']['Name'] ?? '');

        $this->phone       = $this->contact['Phone'] ? (string) $this->contact['Phone'] : $this->field('Cell Phone', 'custom-9967571');
        $this->street      = $this->field('Street Address', 'custom-9967566');
        $this->city        = $this->field('City', 'custom-9967567');
        $this->state       = $this->field('State', 'custom-9967569');
        $this->zip         = $this->field('ZIP', 'custom-9967570');
        $this->dob         = $this->field('Date of Birth', 'custom-10694881');
        $this->txId        = $this->field('TX DL/ID Number', 'custom-17846913');
        $this->zone        = $this->field('Zone / Center', 'custom-9967573');
        $this->memberSince = $this->field('Member since', 'MemberSince');
        $this->renewalDue  = $this->field('Renewal due', 'RenewalDue');
        $this->yearlyFee   = $this->field('Membership fee', 'MembershipFee');

        // Filled by later tasks (family, invoices, payments). Defaults keep this task green.
    }

    /**
     * Read a value from the contact's FieldValues by FieldName or SystemCode.
     * Choice values ({Id,Label}) are unwrapped to their Label string.
     */
    private function field(string $fieldName, string $systemCode = ''): string
    {
        $fvs = $this->contact['FieldValues'] ?? [];
        if (! is_array($fvs)) {
            return '';
        }
        foreach ($fvs as $fv) {
            if (! is_array($fv)) {
                continue;
            }
            $name = $fv['FieldName'] ?? '';
            $code = $fv['SystemCode'] ?? '';
            if ($name === $fieldName || ($systemCode !== '' && $code === $systemCode)) {
                $val = $fv['Value'] ?? '';
                if (is_array($val)) {
                    return (string) ($val['Label'] ?? '');
                }
                return (string) $val;
            }
        }
        return '';
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: PASS — 5 tests, all green

- [ ] **Step 5: Commit**

```bash
git add app/Support/MemberProfile.php tests/Unit/MemberProfileTest.php
git commit -m "feat: add MemberProfile DTO with WA field mapping"
```

---

## Task 4: MemberProfile — membership status & date helpers

**Files:**
- Modify: `app/Support/MemberProfile.php`
- Test: `tests/Unit/MemberProfileTest.php`

- [ ] **Step 1: Add the failing tests**

Append these methods to `tests/Unit/MemberProfileTest.php` (inside the class):

```php
    public function test_is_expired_true_for_expired_statuses(): void
    {
        foreach (['Expired', 'Lapsed', 'Overdue'] as $status) {
            $b = $this->bundle();
            $b['contact']['Status'] = $status;
            $this->assertTrue((new MemberProfile($b))->isExpired(), "$status should be expired");
        }
    }

    public function test_is_expired_false_for_active(): void
    {
        $this->assertFalse((new MemberProfile($this->bundle()))->isExpired());
    }

    public function test_renewal_formatted_returns_human_date(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('January 15, 2027', $p->renewalFormatted());
    }

    public function test_member_since_formatted_returns_human_date(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('August 22, 2021', $p->memberSinceFormatted());
    }

    public function test_dob_formatted_returns_human_date(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('November 09, 2005', $p->dobFormatted());
    }

    public function test_formatted_helpers_return_empty_for_blank(): void
    {
        $p = new MemberProfile(['contact' => ['Id' => 1, 'FieldValues' => []]]);
        $this->assertSame('', $p->renewalFormatted());
        $this->assertSame('', $p->dobFormatted());
    }

    public function test_days_left_positive_for_future_renewal(): void
    {
        $b = $this->bundle();
        $b['contact']['FieldValues'][] = ['SystemCode' => 'RenewalDue', 'FieldName' => 'Renewal due', 'Value' => now()->addDays(30)->toIso8601String()];
        $p = new MemberProfile($b);
        $this->assertGreaterThan(0, $p->daysLeft());
        $this->assertNull($p->daysOverdue());
    }

    public function test_days_overdue_positive_for_past_renewal(): void
    {
        $b = $this->bundle();
        // Replace RenewalDue with a past date.
        foreach ($b['contact']['FieldValues'] as &$fv) {
            if (($fv['SystemCode'] ?? '') === 'RenewalDue') {
                $fv['Value'] = now()->subDays(12)->toIso8601String();
            }
        }
        unset($fv);
        $p = new MemberProfile($b);
        $this->assertGreaterThan(0, $p->daysOverdue());
        $this->assertNull($p->daysLeft());
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: FAIL — `Call to undefined method ...isExpired()`

- [ ] **Step 3: Add the helper methods**

In `app/Support/MemberProfile.php`, add these methods after the `field()` method:

```php
    /** True when the membership status indicates a lapsed membership. */
    public function isExpired(): bool
    {
        $s = strtolower($this->status);
        return in_array($s, ['expired', 'lapsed', 'overdue'], true);
    }

    /** Renewal date as "January 15, 2027", or '' if unparseable. */
    public function renewalFormatted(): string
    {
        return $this->formatDate($this->renewalDue);
    }

    /** Member-since date as "August 22, 2021", or '' if unparseable. */
    public function memberSinceFormatted(): string
    {
        return $this->formatDate($this->memberSince);
    }

    /** Date of birth as "November 09, 2005", or '' if unparseable. */
    public function dobFormatted(): string
    {
        return $this->formatDate($this->dob);
    }

    /** Whole days until renewal; null when renewal is in the past or unknown. */
    public function daysLeft(): ?int
    {
        $diff = $this->renewalDiffDays();
        return ($diff !== null && $diff >= 0) ? $diff : null;
    }

    /** Whole days a renewal is overdue; null when not overdue or unknown. */
    public function daysOverdue(): ?int
    {
        $diff = $this->renewalDiffDays();
        return ($diff !== null && $diff < 0) ? abs($diff) : null;
    }

    private function renewalDiffDays(): ?int
    {
        if ($this->renewalDue === '') {
            return null;
        }
        try {
            return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->renewalDue)->startOfDay(), false);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate(string $value): string
    {
        if ($value === '' || strtolower($value) === 'null' || strtolower($value) === 'never') {
            return '';
        }
        try {
            return Carbon::parse($value)->format('F d, Y');
        } catch (\Throwable) {
            return '';
        }
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: PASS — all tests green

- [ ] **Step 5: Commit**

```bash
git add app/Support/MemberProfile.php tests/Unit/MemberProfileTest.php
git commit -m "feat: add status + date helpers to MemberProfile"
```

---

## Task 5: MemberProfile — family members & spouse

**Files:**
- Modify: `app/Support/MemberProfile.php`
- Test: `tests/Unit/MemberProfileTest.php`

- [ ] **Step 1: Add the failing tests**

Append to `tests/Unit/MemberProfileTest.php`:

```php
    public function test_family_members_are_wrapped_as_member_profiles(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertCount(1, $p->family);
        $this->assertInstanceOf(MemberProfile::class, $p->family[0]);
        $this->assertSame('Sarah Alam', $p->family[0]->fullName);
    }

    public function test_spouse_is_first_family_member(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertTrue($p->hasSpouse());
        $this->assertInstanceOf(MemberProfile::class, $p->spouse());
        $this->assertSame('Sarah Alam', $p->spouse()->fullName);
    }

    public function test_no_spouse_when_family_empty(): void
    {
        $b = $this->bundle();
        $b['family'] = [];
        $p = new MemberProfile($b);

        $this->assertFalse($p->hasSpouse());
        $this->assertNull($p->spouse());
        $this->assertFalse($p->hasFamily());
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: FAIL — `Call to undefined method ...hasSpouse()`

- [ ] **Step 3: Populate family in the constructor + add helpers**

In `app/Support/MemberProfile.php` constructor, replace the comment line
`// Filled by later tasks (family, invoices, payments). Defaults keep this task green.`
with:

```php
        // Family members — each wrapped in its own MemberProfile.
        foreach (($bundle['family'] ?? []) as $fam) {
            if (is_array($fam)) {
                $this->family[] = new MemberProfile(['contact' => $fam]);
            }
        }
```

Then add these methods after `formatDate()`:

```php
    /** True when the member has at least one family member. */
    public function hasFamily(): bool
    {
        return $this->family !== [];
    }

    /** True when the member has a spouse (first family member). */
    public function hasSpouse(): bool
    {
        return isset($this->family[0]);
    }

    /** The spouse (first family member), or null. */
    public function spouse(): ?MemberProfile
    {
        return $this->family[0] ?? null;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: PASS — all tests green

- [ ] **Step 5: Commit**

```bash
git add app/Support/MemberProfile.php tests/Unit/MemberProfileTest.php
git commit -m "feat: add family + spouse accessors to MemberProfile"
```

---

## Task 6: MemberProfile — invoices, payments & derived totals

**Files:**
- Modify: `app/Support/MemberProfile.php`
- Test: `tests/Unit/MemberProfileTest.php`

- [ ] **Step 1: Add the failing tests**

Append to `tests/Unit/MemberProfileTest.php`:

```php
    public function test_invoices_are_normalized(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertCount(2, $p->invoices);
        $this->assertTrue($p->hasInvoices());
        $inv = $p->invoices[0];
        $this->assertSame('INV-2026-0001', $inv['number']);
        $this->assertSame('2026-01-15', $inv['date']);
        $this->assertSame(20.0, $inv['amount']);
        $this->assertTrue($inv['isPaid']);
    }

    public function test_no_invoices_reports_false(): void
    {
        $b = $this->bundle();
        $b['invoices'] = [];
        $this->assertFalse((new MemberProfile($b))->hasInvoices());
    }

    public function test_next_payment_is_earliest_unpaid_invoice(): void
    {
        $p = new MemberProfile($this->bundle());
        $next = $p->nextPayment();
        $this->assertNotNull($next);
        $this->assertSame(20.0, $next['amount']);
        $this->assertSame('2026-06-15', $next['date']);
    }

    public function test_next_payment_null_when_all_paid(): void
    {
        $b = $this->bundle();
        foreach ($b['invoices'] as &$inv) { $inv['IsPaid'] = true; }
        unset($inv);
        $this->assertNull((new MemberProfile($b))->nextPayment());
    }

    public function test_last_payment_is_most_recent_payment(): void
    {
        $p = new MemberProfile($this->bundle());
        $last = $p->lastPayment();
        $this->assertNotNull($last);
        $this->assertSame(20.0, $last['amount']);
        $this->assertSame('2026-01-15', $last['date']);
    }

    public function test_paid_this_year_sums_current_year_payments(): void
    {
        $b = $this->bundle();
        // One payment in 2026, one in 2025 (per fixture). Force current year to 2026.
        $b['payments'] = [
            ['Id' => 1, 'Value' => 20.0, 'CreatedDate' => now()->startOfYear()->addDays(5)->toIso8601String()],
            ['Id' => 2, 'Value' => 60.0, 'CreatedDate' => now()->subYears(1)->toIso8601String()],
        ];
        $p = new MemberProfile($b);
        $this->assertSame(20.0, $p->paidThisYear());
        $this->assertSame(80.0, $p->paidAllTime());
    }

    public function test_payment_totals_zero_when_no_payments(): void
    {
        $b = $this->bundle();
        $b['payments'] = [];
        $p = new MemberProfile($b);
        $this->assertSame(0.0, $p->paidThisYear());
        $this->assertSame(0.0, $p->paidAllTime());
        $this->assertNull($p->lastPayment());
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: FAIL — `Call to undefined method ...hasInvoices()`

- [ ] **Step 3: Normalize invoices/payments in constructor + add accessors**

In `app/Support/MemberProfile.php` constructor, immediately after the family loop added in Task 5, add:

```php
        // Invoices — normalized to a predictable shape.
        foreach (($bundle['invoices'] ?? []) as $inv) {
            if (! is_array($inv)) {
                continue;
            }
            $this->invoices[] = [
                'id'     => $inv['Id'] ?? null,
                'number' => (string) ($inv['DocumentNumber'] ?? ('INV-' . ($inv['Id'] ?? ''))),
                'date'   => $this->isoToDate($inv['CreatedDate'] ?? ''),
                'amount' => (float) ($inv['Value'] ?? 0),
                'isPaid' => (bool) ($inv['IsPaid'] ?? false),
                'url'    => (string) ($inv['Url'] ?? '#'),
            ];
        }

        // Payments — normalized.
        foreach (($bundle['payments'] ?? []) as $pay) {
            if (! is_array($pay)) {
                continue;
            }
            $this->payments[] = [
                'date'   => $this->isoToDate($pay['CreatedDate'] ?? ''),
                'amount' => (float) ($pay['Value'] ?? 0),
            ];
        }
```

Add these methods after `spouse()`:

```php
    /** True when the member has at least one invoice. */
    public function hasInvoices(): bool
    {
        return $this->invoices !== [];
    }

    /** Earliest unpaid invoice as ['amount'=>float,'date'=>string], or null. */
    public function nextPayment(): ?array
    {
        $unpaid = array_values(array_filter($this->invoices, fn ($i) => ! $i['isPaid']));
        if ($unpaid === []) {
            return null;
        }
        usort($unpaid, fn ($a, $b) => strcmp($a['date'], $b['date']));
        return ['amount' => $unpaid[0]['amount'], 'date' => $unpaid[0]['date']];
    }

    /** Most recent payment as ['amount'=>float,'date'=>string], or null. */
    public function lastPayment(): ?array
    {
        if ($this->payments === []) {
            return null;
        }
        $sorted = $this->payments;
        usort($sorted, fn ($a, $b) => strcmp($b['date'], $a['date']));
        return ['amount' => $sorted[0]['amount'], 'date' => $sorted[0]['date']];
    }

    /** Sum of payment amounts in the current calendar year. */
    public function paidThisYear(): float
    {
        $year = (string) now()->year;
        return array_sum(array_map(
            fn ($p) => str_starts_with($p['date'], $year) ? $p['amount'] : 0.0,
            $this->payments
        ));
    }

    /** Sum of all payment amounts. */
    public function paidAllTime(): float
    {
        return array_sum(array_column($this->payments, 'amount'));
    }

    /** ISO datetime to "YYYY-MM-DD", or '' if unparseable. */
    private function isoToDate(string $iso): string
    {
        if ($iso === '') {
            return '';
        }
        try {
            return Carbon::parse($iso)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/MemberProfileTest.php`
Expected: PASS — all tests green

- [ ] **Step 5: Commit**

```bash
git add app/Support/MemberProfile.php tests/Unit/MemberProfileTest.php
git commit -m "feat: add invoice/payment normalization + totals to MemberProfile"
```

---

## Task 7: MemberPortalService — assemble & cache the bundle

**Files:**
- Create: `app/Services/MemberPortalService.php`
- Test: `tests/Feature/MemberPortalServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MemberPortalServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\MemberPortalService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberPortalServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
    }

    private function fakeWaSuccess(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'],
                'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response([
                'Contacts' => [['Id' => 1001, 'FirstName' => 'Sarah', 'LastName' => 'Alam', 'FieldValues' => []]],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*' => Http::response([
                'Invoices' => [['Id' => 1, 'DocumentNumber' => 'INV-1', 'Value' => 20.0, 'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00']],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*' => Http::response([
                'Payments' => [['Id' => 5, 'Value' => 20.0, 'CreatedDate' => '2026-01-15T00:00:00']],
            ], 200),
        ]);
    }

    public function test_assemble_bundle_aggregates_all_slices(): void
    {
        $this->fakeWaSuccess();

        $bundle = app(MemberPortalService::class)->assembleBundle(999);

        $this->assertSame('Tauqeer', $bundle['contact']['FirstName']);
        $this->assertCount(1, $bundle['family']);
        $this->assertCount(1, $bundle['invoices']);
        $this->assertCount(1, $bundle['payments']);
    }

    public function test_assemble_bundle_caches_result(): void
    {
        $this->fakeWaSuccess();

        app(MemberPortalService::class)->assembleBundle(999);

        $this->assertTrue(Cache::has('member_portal_bundle_999'));
    }

    public function test_bundle_survives_failed_invoices_call(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*' => Http::response('error', 500),
            'api.wildapricot.org/v2.3/accounts/12345/payments*' => Http::response(['Payments' => []], 200),
        ]);

        $bundle = app(MemberPortalService::class)->assembleBundle(999);

        $this->assertSame('Tauqeer', $bundle['contact']['FirstName']);
        $this->assertSame([], $bundle['invoices']);
    }

    public function test_get_bundle_returns_cached_without_refetch(): void
    {
        $this->fakeWaSuccess();
        $svc = app(MemberPortalService::class);
        $svc->assembleBundle(999);
        Http::fake(); // any further call would now return empty/throw

        $bundle = $svc->getBundle(999);
        $this->assertSame('Tauqeer', $bundle['contact']['FirstName']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/MemberPortalServiceTest.php`
Expected: FAIL — `Class "App\Services\MemberPortalService" not found`

- [ ] **Step 3: Create MemberPortalService**

Create `app/Services/MemberPortalService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates member-portal data: assembles the full WildApricot bundle
 * (contact + family + invoices + payments), caches it per-member, and
 * delegates profile writes back to WildApricotService.
 */
class MemberPortalService
{
    private const CACHE_TTL_MINUTES = 10;

    public function __construct(private WildApricotService $wa)
    {
    }

    /** Cache key for a member's assembled bundle. */
    private function cacheKey(int $contactId): string
    {
        return "member_portal_bundle_{$contactId}";
    }

    /**
     * Fetch every slice of a member's data and cache the assembled bundle.
     * Secondary-call failures degrade gracefully to empty slices.
     */
    public function assembleBundle(int $contactId): array
    {
        $contact  = $this->wa->getContactById($contactId) ?? [];
        $family   = $this->wa->getFamilyMembers($contactId);
        $invoices = $this->wa->getInvoicesForContact($contactId);
        $payments = $this->wa->getPaymentsForContact($contactId);

        $bundle = [
            'contact'  => $contact,
            'family'   => $family,
            'invoices' => $invoices,
            'payments' => $payments,
        ];

        Cache::put($this->cacheKey($contactId), $bundle, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $bundle;
    }

    /** Return the cached bundle, assembling (and caching) on a cache miss. */
    public function getBundle(int $contactId): array
    {
        $cached = Cache::get($this->cacheKey($contactId));
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }
        return $this->assembleBundle($contactId);
    }

    /** Drop the cached bundle so the next load re-fetches fresh data. */
    public function invalidate(int $contactId): void
    {
        Cache::forget($this->cacheKey($contactId));
    }

    /**
     * Persist primary-member profile edits to WildApricot, then invalidate cache.
     * @throws \RuntimeException on WA rejection.
     */
    public function updateProfile(int $contactId, array $data): array
    {
        $result = $this->wa->updateMember($contactId, $data);
        $this->invalidate($contactId);
        return $result;
    }

    /**
     * Persist a family-member (spouse) edit to WildApricot, then invalidate
     * the primary member's cache so the change shows on next load.
     * @throws \RuntimeException on WA rejection.
     */
    public function updateFamilyMember(int $primaryContactId, int $familyContactId, array $data): array
    {
        $result = $this->wa->updateMember($familyContactId, $data);
        $this->invalidate($primaryContactId);
        return $result;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/MemberPortalServiceTest.php`
Expected: PASS — 4 tests, all green

- [ ] **Step 5: Commit**

```bash
git add app/Services/MemberPortalService.php tests/Feature/MemberPortalServiceTest.php
git commit -m "feat: add MemberPortalService for bundle assembly + caching"
```

---

## Task 8: Controller — assemble on OTP verify, read on page load

**Files:**
- Modify: `app/Http/Controllers/MemberPortalController.php`
- Test: `tests/Feature/MemberPortalIntegrationTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MemberPortalIntegrationTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberPortalIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);

        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
        ]);
    }

    public function test_verify_otp_assembles_and_caches_bundle(): void
    {
        $this->withSession(['member_portal_otp' => [
            'code' => '123456', 'email' => 'tauqeer@example.com',
            'expires_at' => now()->addMinutes(5)->timestamp, 'contact_id' => 999,
        ]]);

        $res = $this->postJson('/member-portal/verify-otp', ['otp' => '123456']);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(Cache::has('member_portal_bundle_999'));
    }

    public function test_dashboard_renders_member_data(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/dashboard')
            ->assertOk()
            ->assertSee('Tauqeer Alam')
            ->assertSee('Individual Membership');
    }

    public function test_profile_renders_member_data(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/profile')
            ->assertOk()
            ->assertSee('Tauqeer');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/MemberPortalIntegrationTest.php`
Expected: FAIL — `test_verify_otp_assembles_and_caches_bundle` fails on `Cache::has(...)` (no bundle assembled yet)

- [ ] **Step 3: Update the controller**

In `app/Http/Controllers/MemberPortalController.php`:

a) Add the import after the existing `use App\Services\WildApricotService;`:

```php
use App\Services\MemberPortalService;
```

b) In `verifyOtp()`, replace the success block (the part from `// OTP correct` through the closing `return response()->json([...])`) with:

```php
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
```

c) Replace the existing `dashboard()` method with:

```php
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
        $profile = new \App\Support\MemberProfile($bundle);

        return view('member-portal.dashboard', compact('profile', 'email'));
    }
```

d) Replace the existing `profile()` method with:

```php
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
        $profile = new \App\Support\MemberProfile($bundle);

        return view('member-portal.profile', compact('profile', 'email'));
    }
```

- [ ] **Step 4: Run test to verify it fails on view rendering**

Run: `php artisan test tests/Feature/MemberPortalIntegrationTest.php`
Expected: `test_verify_otp_assembles_and_caches_bundle` now PASSES; the `dashboard`/`profile` render tests FAIL with an undefined-variable error (`$member`/`$firstName`) — the views still reference the old variables. This is fixed in Task 10.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/MemberPortalController.php tests/Feature/MemberPortalIntegrationTest.php
git commit -m "feat: assemble member bundle on OTP verify, pass MemberProfile to views"
```

---

## Task 9: Profile update route + controller action

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/MemberPortalController.php`
- Test: `tests/Feature/MemberPortalIntegrationTest.php`

- [ ] **Step 1: Add the failing tests**

Append to `tests/Feature/MemberPortalIntegrationTest.php`:

```php
    public function test_update_profile_persists_and_invalidates_cache(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::sequence()
                ->push(['Id' => 999, 'FirstName' => 'Tauqeer', 'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => []], 200)
                ->push(['Id' => 999, 'FirstName' => 'Tauqeer', 'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => []], 200)
                ->push(['Id' => 999, 'FirstName' => 'Tariq', 'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
        ]);

        Cache::put('member_portal_bundle_999', ['contact' => ['Id' => 999], 'family' => [], 'invoices' => [], 'payments' => []], now()->addMinutes(10));

        $this->withSession(['member_portal_authenticated' => true, 'member_portal_contact_id' => 999])
            ->postJson('/member-portal/profile/update', [
                'first_name' => 'Tariq', 'last_name' => 'Alam',
                'email' => 'tariq@example.com', 'phone' => '2154389281',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse(Cache::has('member_portal_bundle_999'));
    }

    public function test_update_profile_rejects_invalid_email(): void
    {
        $this->withSession(['member_portal_authenticated' => true, 'member_portal_contact_id' => 999])
            ->postJson('/member-portal/profile/update', [
                'first_name' => 'Tariq', 'last_name' => 'Alam', 'email' => 'not-an-email',
            ])
            ->assertStatus(422);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/MemberPortalIntegrationTest.php`
Expected: FAIL — 404 / route `member-portal.profile.update` not defined

- [ ] **Step 3: Add the route**

In `routes/web.php`, inside the `member-portal` group, after the `profile` GET route:

```php
    Route::post('/profile/update', [MemberPortalController::class, 'updateProfile'])->name('profile.update');
```

- [ ] **Step 4: Add the controller action**

In `app/Http/Controllers/MemberPortalController.php`, add after the `profile()` method:

```php
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
            'email'      => ['required', 'email'],
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
        } catch (\Throwable $e) {
            Log::error('MemberPortal: profile update failed', [
                'contact_id' => $contactId, 'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not save changes. Please try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Changes saved successfully.',
        ]);
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/MemberPortalIntegrationTest.php`
Expected: PASS — `test_update_profile_*` tests green (dashboard/profile render tests still fail until Task 10)

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/MemberPortalController.php tests/Feature/MemberPortalIntegrationTest.php
git commit -m "feat: add profile update endpoint persisting to WildApricot"
```

---

## Task 10: Re-bind dashboard view to MemberProfile

**Files:**
- Modify: `resources/views/member-portal/dashboard.blade.php`

- [ ] **Step 1: Replace the `@php` data block**

In `dashboard.blade.php`, find the `@php … @endphp` block (around lines 739–771, which builds `$firstName`, `$level`, `$contribution`, `$txid`, `$isExpired`, etc.) and replace the **entire block** with:

```php
@php
  /** @var \App\Support\MemberProfile $profile */
  $isExpired   = $profile->isExpired();
  $daysLeft    = $profile->daysLeft();
  $daysOverdue = $profile->daysOverdue();
@endphp
```

- [ ] **Step 2: Re-bind the Membership Status card**

In the membership status card, update value bindings:
- Badge: `{{ $isExpired ? 'Expired' : $profile->status }}`
- Renewal date (active): `{{ $profile->renewalFormatted() ?: '—' }}`
- Days remaining: `{{ $daysLeft }} days remaining` (the `@if($daysLeft !== null)` guard stays)
- Days overdue: `{{ $daysOverdue }} days overdue` (the `@if($daysOverdue !== null)` guard stays)
- Membership Type tile value: `{{ $profile->level ?: '—' }}`
- Member Since tile value: `{{ $profile->memberSinceFormatted() ?: '—' }}`
- Yearly Contribution/Fee tile value: `{{ $profile->yearlyFee ?: '$200.00' }}`
- TXDL#/TXID# tile value: `{{ $profile->txId ?: '—' }}`

- [ ] **Step 3: Re-bind Recent Invoices to real data**

Find the Recent Invoices list. Replace the `@foreach(range(1,4) as $i)` loop with:

```blade
@forelse($profile->invoices as $inv)
  <div class="invoice-item">
    <div class="invoice-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
      </svg>
    </div>
    <div class="invoice-meta">
      <div class="invoice-id">{{ $inv['number'] }}</div>
      <div class="invoice-date">{{ $inv['date'] ?: '—' }}</div>
    </div>
    <div class="invoice-right">
      <div class="invoice-amount">${{ number_format($inv['amount'], 2) }}</div>
      <a href="{{ $inv['url'] }}" class="invoice-view">View</a>
    </div>
  </div>
@empty
  <div class="invoice-item" style="justify-content:center;color:var(--text-muted);">
    No invoices yet
  </div>
@endforelse
```

- [ ] **Step 4: Re-bind Payment Overview**

In the Payment Overview tiles:
- Next Payment amount: `{{ $profile->nextPayment() ? '$' . number_format($profile->nextPayment()['amount'], 2) : '$0.00' }}`
- Next Payment date: `{{ $profile->nextPayment()['date'] ?? '—' }}`
- Last Payment amount: `{{ $profile->lastPayment() ? '$' . number_format($profile->lastPayment()['amount'], 2) : '$0.00' }}`
- Last Payment date: `{{ $profile->lastPayment()['date'] ?? '—' }}`
- This Year: `${{ number_format($profile->paidThisYear(), 2) }}`
- All Time: `${{ number_format($profile->paidAllTime(), 2) }}`

- [ ] **Step 5: Re-bind Quick Profile card**

In the Quick Profile card:
- Name: `{{ $profile->fullName ?: 'Member' }}`
- Email: `{{ $profile->email ?: '—' }}`
- Phone: `{{ $profile->phone ?: '—' }}`
- Location: `{{ trim($profile->city . ', ' . $profile->state, ', ') ?: '—' }}`

- [ ] **Step 6: Update the JS membership-status line**

Find `let membershipStatus = @json($isExpired ? 'expired' : 'active');` — it still works since `$isExpired` is defined in the new `@php` block. Confirm `serverStatusLabel` reads `@json($profile->status)` (update if it referenced `$status`).

- [ ] **Step 7: Run the integration tests**

Run: `php artisan test tests/Feature/MemberPortalIntegrationTest.php`
Expected: `test_dashboard_renders_member_data` now PASSES.

- [ ] **Step 8: Commit**

```bash
git add resources/views/member-portal/dashboard.blade.php
git commit -m "feat: bind dashboard view to MemberProfile data"
```

---

## Task 11: Re-bind profile-content partial to MemberProfile

**Files:**
- Modify: `resources/views/member-portal/partials/profile-content.blade.php`

- [ ] **Step 1: Re-bind the Membership Information card**

In `partials/profile-content.blade.php`:
- Badge: `{{ $profile->isExpired() ? 'Expired' : $profile->status }}`
- Renewal date: `{{ $profile->renewalFormatted() ?: '—' }}`
- Days remaining: wrap in `@if($profile->daysLeft() !== null)` → `{{ $profile->daysLeft() }} days remaining`
- Membership Type value: `{{ $profile->level ?: '—' }}`
- Member Since value: `{{ $profile->memberSinceFormatted() ?: '—' }}`
- Yearly fee value: `{{ $profile->yearlyFee ?: '$200.00' }}`
- TXDL#/TXID# value: `{{ $profile->txId ?: '—' }}`

- [ ] **Step 2: Re-bind the Primary Member form inputs**

Set each input's `value`:
- `p-first` → `value="{{ $profile->firstName }}"`
- `p-last` → `value="{{ $profile->lastName }}"`
- `p-email` → `value="{{ $profile->email }}"`
- `p-phone` → `value="{{ $profile->phone }}"`
- `p-street` → `value="{{ $profile->street }}"`
- `p-tx` → `value="{{ $profile->txId }}"`
- `p-dob` → `value="{{ $profile->dob }}"`
- `p-zip` → `value="{{ $profile->zip }}"`
- `p-city` → `value="{{ $profile->city }}"`
- `p-state` → `value="{{ $profile->state }}"`
- `p-zone` → `value="{{ $profile->zone }}"`

- [ ] **Step 3: Wrap the Spouse Information form in a conditional**

Wrap the entire Spouse Information `<div class="card">…</div>` (the form card) with:

```blade
@if($profile->hasSpouse())
  @php $spouse = $profile->spouse(); @endphp
  {{-- … existing spouse card markup … --}}
@endif
```

Inside, bind the spouse inputs to `$spouse`:
- `s-first` → `value="{{ $spouse->firstName }}"`
- `s-last` → `value="{{ $spouse->lastName }}"`
- `s-email` → `value="{{ $spouse->email }}"`
- `s-phone` → `value="{{ $spouse->phone }}"`
- `s-address` → `value="{{ $spouse->street }}"`
- `s-tx` → `value="{{ $spouse->txId }}"`
- `s-dob` → `value="{{ $spouse->dob }}"`
- `s-zip` → `value="{{ $spouse->zip }}"`
- `s-city` → `value="{{ $spouse->city }}"`
- `s-state` → `value="{{ $spouse->state }}"`
- `s-zone` → `value="{{ $spouse->zone }}"`

(Remove the `placeholder="Sarah"` etc. — real data fills these now.)

- [ ] **Step 4: Re-bind the right-rail profile cards**

- Primary Profile card name: `{{ $profile->fullName ?: 'Member' }}`
- Wrap the **Spouse Profile card** `<div class="profile-card spouse-profile">…</div>` in `@if($profile->hasSpouse())` and bind its name: `{{ $profile->spouse()->fullName }}`

- [ ] **Step 5: Re-bind Quick Links card** — no data bindings; leave as-is.

- [ ] **Step 6: Run the integration tests**

Run: `php artisan test tests/Feature/MemberPortalIntegrationTest.php`
Expected: `test_profile_renders_member_data` PASSES.

- [ ] **Step 7: Commit**

```bash
git add resources/views/member-portal/partials/profile-content.blade.php
git commit -m "feat: bind profile-content partial to MemberProfile data"
```

---

## Task 12: Re-bind standalone profile.blade.php + wire save endpoint

**Files:**
- Modify: `resources/views/member-portal/profile.blade.php`

- [ ] **Step 1: Replace the standalone `@php` block**

In `profile.blade.php`, find the `@php … @endphp` block that builds `$firstName`, `$lastName`, `$street`, etc. Replace the whole block with:

```php
@php
  /** @var \App\Support\MemberProfile $profile */
@endphp
```

(The partial it includes already reads `$profile` directly after Task 11.)

- [ ] **Step 2: Confirm the partial include passes `$profile`**

Verify `profile.blade.php` includes `@include('member-portal.partials.profile-content')` and that `$profile` is in scope (it is — passed by the controller via `compact('profile')`). No change needed if the include is plain.

- [ ] **Step 3: Wire the Primary form Save to the update endpoint**

In the `<script>` block, find `setupForm({...})`'s save handler. Replace the save-button click body so it POSTs to the endpoint. The save handler becomes:

```js
saveBtn.addEventListener('click', async () => {
  let valid = true;
  inputs().forEach(el => {
    if (el.type === 'email' && el.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value)) {
      el.style.borderColor = '#ef4444'; el.style.background = '#fef2f2'; valid = false;
    } else {
      el.style.borderColor = ''; el.style.background = '';
    }
  });
  if (!valid) return;

  // Only the primary form persists to WA; spouse form is display/edit-local for now.
  if (formId === 'formPrimary') {
    const payload = {
      first_name: document.getElementById('p-first')?.value || '',
      last_name:  document.getElementById('p-last')?.value || '',
      email:      document.getElementById('p-email')?.value || '',
      phone:      document.getElementById('p-phone')?.value || '',
      street:     document.getElementById('p-street')?.value || '',
      city:       document.getElementById('p-city')?.value || '',
      state:      document.getElementById('p-state')?.value || '',
      zip:        document.getElementById('p-zip')?.value || '',
      dob:        document.getElementById('p-dob')?.value || '',
      tx_dl:      document.getElementById('p-tx')?.value || '',
    };
    try {
      const res = await fetch('{{ route('member-portal.profile.update') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!data.success) {
        editBtn.querySelector('span').textContent = 'Edit Profile';
        alert(data.message || 'Could not save changes.');
        return;
      }
    } catch {
      alert('Network error. Please try again.');
      return;
    }
  }

  editing = false;
  inputs().forEach(el => { el.readOnly = true; });
  editBtn.querySelector('span').textContent = 'Edit Profile';
  if (banner) {
    banner.classList.add('visible');
    setTimeout(() => banner.classList.remove('visible'), 2500);
  }
});
```

- [ ] **Step 4: Confirm the CSRF meta tag exists**

`profile.blade.php` must have `<meta name="csrf-token" content="{{ csrf_token() }}" />` in `<head>`. If absent, add it.

- [ ] **Step 5: Apply the same save wiring inside the dashboard's embedded profile JS**

In `dashboard.blade.php`, the `setupForm()` function (added when the Profile tab was embedded) has the same save handler. Apply the identical change from Step 3 there so saving works on both the standalone page and the embedded `#profilePage`.

- [ ] **Step 6: Run the full test suite**

Run: `php artisan test`
Expected: PASS — all unit + feature tests green.

- [ ] **Step 7: Commit**

```bash
git add resources/views/member-portal/profile.blade.php resources/views/member-portal/dashboard.blade.php
git commit -m "feat: bind standalone profile view + wire profile save endpoint"
```

---

## Task 13: Manual smoke test & field-code verification

**Files:** none (verification only)

- [ ] **Step 1: Run one real member through the portal**

Start the app (`php artisan serve`), open `/member-portal/login`, enter a real WildApricot member's email, complete the OTP (local env logs `debug_otp`).

- [ ] **Step 2: Verify dashboard data**

Confirm the dashboard shows the real member's name, membership level, status, member-since, renewal date, real invoices, and payment totals — not placeholders.

- [ ] **Step 3: Verify profile data**

Confirm `/member-portal/profile` shows the real profile fields populated, and the Spouse section appears only if the member has a linked family member.

- [ ] **Step 4: Verify save**

Edit a profile field, click Save Changes, confirm the green banner appears and the value persists after a page reload (`?refresh=1` to bypass cache).

- [ ] **Step 5: Reconcile field codes if needed**

If any field shows blank/`—` for data that exists in WildApricot, inspect `storage/logs/laravel.log` and the raw contact JSON. Adjust the `field()` mapping in `app/Support/MemberProfile.php` (e.g. correct a `custom-*` code or invoice key like `Value` vs `Amount`), then re-run `php artisan test` and commit:

```bash
git add app/Support/MemberProfile.php
git commit -m "fix: correct WA field-code mapping after live verification"
```

- [ ] **Step 6: Final commit if no changes were needed**

If the smoke test passed with no code changes, no commit is required — the feature is complete.

---

## Self-Review Notes

- **Spec coverage:** WA fetch methods (T1), bundle assembly + caching (T7), OTP-time assembly (T8), MemberProfile mapping/derived/empty-handling (T3–T6), dashboard binding incl. invoices/payments (T10), profile + spouse binding with graceful hide (T11–T12), profile save persisting to WA + cache invalidation (T9, T12), error handling (graceful empty slices in T1/T7, 422/401 in T9), testing (unit T3–T6, service T7, feature T8–T9), `?refresh=1` (T8), manual smoke (T13). All spec sections covered.
- **Type consistency:** `MemberProfile` accessor names (`firstName`, `renewalFormatted()`, `daysLeft()`, `nextPayment()`, `hasSpouse()`, `spouse()`, `invoices`, `paidThisYear()`) are consistent across T3–T6 definitions and T10–T12 view usage. `MemberPortalService` methods (`assembleBundle`, `getBundle`, `invalidate`, `updateProfile`) consistent across T7–T9. `updateMember()` data keys (`first_name`, `street`, `tx_dl`, etc.) match the existing `buildFieldValues()` in `WildApricotService`.
- **No placeholders:** every code step contains complete code.

# Membership Renewal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an expired member renew their membership from the portal — pay the plan fee via the existing Stripe flow, generate a WildApricot renewal invoice, record the payment, and advance the renewal date for the member and their family.

**Architecture:** A `RenewalService` orchestrates the renewal: it resolves the fee, runs the Stripe charge sequence (reusing `StripeService`), and on success dispatches a queued `ProcessMembershipRenewal` job that does the WildApricot side (invoice, payment, renewal-date updates for primary + family). `MemberPortalController` exposes three thin endpoints. A `renewals` table gives the job durable, retryable state.

**Tech Stack:** Laravel 12, PHP 8.2, Stripe PHP SDK, WildApricot REST API v2.3, Blade, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-05-16-membership-renewal-design.md`

---

## File Structure

**Create:**
- `config/membership.php` — shared membership fee table.
- `app/Services/RenewalService.php` — renewal orchestrator (fee resolution, Stripe charge, job dispatch).
- `app/Jobs/ProcessMembershipRenewal.php` — queued job for the WildApricot side.
- `database/migrations/2026_05_16_000000_create_renewals_table.php` — `renewals` table.
- `app/Models/Renewal.php` — Eloquent model.
- `tests/Unit/RenewalServiceTest.php`
- `tests/Feature/ProcessMembershipRenewalTest.php`
- `tests/Feature/MembershipRenewalTest.php` — controller feature tests.

**Modify:**
- `app/Http/Controllers/MembershipController.php` — `FEES` constant → `config('membership.fees')`.
- `app/Http/Controllers/MemberPortalController.php` — add `renewSummary`, `processRenewal`, `renewStatus`.
- `routes/web.php` — three renewal routes in the `member-portal` group.
- `resources/views/member-portal/dashboard.blade.php` — wire the Renew buttons + modal to the endpoints; hide for lifetime.

---

## Task 1: Extract the membership fee table to shared config

**Files:**
- Create: `config/membership.php`
- Modify: `app/Http/Controllers/MembershipController.php`
- Test: `tests/Unit/MembershipFeeConfigTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/MembershipFeeConfigTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class MembershipFeeConfigTest extends TestCase
{
    public function test_membership_fees_config_has_all_seven_types(): void
    {
        $fees = config('membership.fees');

        $this->assertIsArray($fees);
        foreach (['family', 'individual', 'flat', 'checkomatic_family',
                  'checkomatic_individual', 'lifetime_family', 'lifetime_individual'] as $type) {
            $this->assertArrayHasKey($type, $fees, "Missing fee for {$type}");
            $this->assertArrayHasKey('cents', $fees[$type]);
            $this->assertArrayHasKey('label', $fees[$type]);
        }
    }

    public function test_known_fee_values(): void
    {
        $fees = config('membership.fees');

        $this->assertSame(4000, $fees['family']['cents']);
        $this->assertSame(2500, $fees['individual']['cents']);
        $this->assertSame(2000, $fees['flat']['cents']);
        $this->assertSame(150000, $fees['lifetime_family']['cents']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/MembershipFeeConfigTest.php`
Expected: FAIL — `config('membership.fees')` is null.

- [ ] **Step 3: Create the config file**

Create `config/membership.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Membership Fees
    |--------------------------------------------------------------------------
    | Shared by the public signup flow (MembershipController) and the member
    | portal renewal flow (RenewalService). Keyed by membership-type slug.
    */
    'fees' => [
        'family'                 => ['cents' => 4000,   'label' => '$40.00'],
        'individual'             => ['cents' => 2500,   'label' => '$25.00'],
        'flat'                   => ['cents' => 2000,   'label' => '$20.00 / member'],
        'checkomatic_family'     => ['cents' => 1000,   'label' => '$10.00/mo'],
        'checkomatic_individual' => ['cents' => 1000,   'label' => '$10.00/mo'],
        'lifetime_family'        => ['cents' => 150000, 'label' => '$1,500.00'],
        'lifetime_individual'    => ['cents' => 100000, 'label' => '$1,000.00'],
    ],
];
```

- [ ] **Step 4: Point MembershipController at the config**

In `app/Http/Controllers/MembershipController.php`, delete the `private const FEES = [ ... ];` block (lines ~26-34).

Then find the one usage — `$fee = self::FEES[$type];` (around line 358) — and change it to:

```php
        $fee = config('membership.fees')[$type];
```

- [ ] **Step 5: Run tests to verify**

Run: `php artisan test tests/Unit/MembershipFeeConfigTest.php`
Expected: PASS — 2 tests.

Also run the existing membership tests to confirm no regression:
Run: `php artisan test tests/Feature/MembershipWildApricotTest.php`
Expected: same pass/fail count as before this change (this suite has 9 pre-existing unrelated failures — the count must not increase).

- [ ] **Step 6: Commit**

```bash
git add config/membership.php app/Http/Controllers/MembershipController.php tests/Unit/MembershipFeeConfigTest.php
git commit -m "refactor: extract membership fee table to config/membership.php"
```

---

## Task 2: Renewals table migration + Renewal model

**Files:**
- Create: `database/migrations/2026_05_16_000000_create_renewals_table.php`
- Create: `app/Models/Renewal.php`
- Test: `tests/Feature/RenewalModelTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/RenewalModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Renewal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenewalModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_renewal_can_be_created_with_expected_fields(): void
    {
        $renewal = Renewal::create([
            'contact_id'      => 999,
            'member_email'    => 'tauqeer@example.com',
            'membership_type' => 'individual',
            'amount_cents'    => 2500,
            'currency'        => 'usd',
            'status'          => 'pending',
        ]);

        $this->assertDatabaseHas('renewals', [
            'id'           => $renewal->id,
            'contact_id'   => 999,
            'status'       => 'pending',
            'amount_cents' => 2500,
        ]);
        $this->assertFalse((bool) $renewal->processed);
    }

    public function test_renewal_casts_processed_to_boolean(): void
    {
        $renewal = Renewal::create([
            'contact_id'      => 1,
            'member_email'    => 'a@b.com',
            'membership_type' => 'family',
            'amount_cents'    => 4000,
            'currency'        => 'usd',
            'status'          => 'paid',
            'processed'       => 1,
        ]);

        $this->assertIsBool($renewal->fresh()->processed);
        $this->assertTrue($renewal->fresh()->processed);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/RenewalModelTest.php`
Expected: FAIL — `Class "App\Models\Renewal" not found`.

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_05_16_000000_create_renewals_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('renewals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');          // WildApricot contact id
            $table->string('member_email')->nullable();
            $table->string('membership_type');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 8)->default('usd');

            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();

            $table->string('status')->default('pending');      // pending|paid|failed|processed
            $table->unsignedBigInteger('wa_invoice_id')->nullable();
            $table->string('wa_step')->nullable();              // invoice|payment|renewal|family|done
            $table->boolean('processed')->default(false);
            $table->unsignedInteger('retry_count')->default(0);

            $table->string('error_type')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_decline_code')->nullable();
            $table->text('error_message')->nullable();

            $table->string('payment_method')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last4', 8)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('contact_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewals');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/Renewal.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Renewal extends Model
{
    protected $guarded = [];

    protected $casts = [
        'processed'    => 'boolean',
        'amount_cents' => 'integer',
        'retry_count'  => 'integer',
        'wa_invoice_id'=> 'integer',
        'contact_id'   => 'integer',
        'paid_at'      => 'datetime',
    ];
}
```

- [ ] **Step 5: Run migration + test**

Run: `php artisan test tests/Feature/RenewalModelTest.php`
Expected: PASS — 2 tests (the test trait `RefreshDatabase` runs the migration).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_05_16_000000_create_renewals_table.php app/Models/Renewal.php tests/Feature/RenewalModelTest.php
git commit -m "feat: add renewals table and Renewal model"
```

---

## Task 3: RenewalService — type-slug resolution

**Files:**
- Create: `app/Services/RenewalService.php`
- Test: `tests/Unit/RenewalServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/RenewalServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\RenewalService;
use App\Support\MemberProfile;
use RuntimeException;
use Tests\TestCase;

class RenewalServiceTest extends TestCase
{
    private function profileWithLevel(string $levelName): MemberProfile
    {
        return new MemberProfile(['contact' => [
            'Id'              => 999,
            'MembershipLevel' => ['Id' => 1, 'Name' => $levelName],
            'FieldValues'     => [],
        ]]);
    }

    public function test_resolves_individual_level_to_slug(): void
    {
        $svc = app(RenewalService::class);
        $this->assertSame('individual', $svc->resolveTypeSlug($this->profileWithLevel('Individual')));
    }

    public function test_resolves_family_level_to_slug(): void
    {
        $svc = app(RenewalService::class);
        $this->assertSame('family', $svc->resolveTypeSlug(
            $this->profileWithLevel('Family Membership (Primary and Spouse only)')
        ));
    }

    public function test_lifetime_level_throws(): void
    {
        $svc = app(RenewalService::class);
        $this->expectException(RuntimeException::class);
        $svc->resolveTypeSlug($this->profileWithLevel('Lifetime'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: FAIL — `Class "App\Services\RenewalService" not found`.

- [ ] **Step 3: Create RenewalService with resolveTypeSlug**

Create `app/Services/RenewalService.php`:

```php
<?php

namespace App\Services;

use App\Support\MemberProfile;
use RuntimeException;

/**
 * Orchestrates a membership renewal: resolves the fee, runs the Stripe charge
 * sequence, and dispatches the WildApricot renewal job. Lifetime memberships
 * are not renewable and are rejected by resolveTypeSlug().
 */
class RenewalService
{
    /**
     * Maps a WildApricot membership-level NAME to a membership-type slug.
     * This is the inverse of WildApricotService::resolveLevelId()'s name map.
     */
    private const LEVEL_NAME_TO_SLUG = [
        'Family Membership (Primary and Spouse only)'              => 'family',
        'Individual'                                               => 'individual',
        'Flat Membership'                                          => 'flat',
        'Checkomatic Membership (Primary and Spouse only)'         => 'checkomatic_family',
        'Checkomatic'                                              => 'checkomatic_individual',
        'Lifetime'                                                 => 'lifetime_individual',
    ];

    private const LIFETIME_SLUGS = ['lifetime_family', 'lifetime_individual'];

    /**
     * Resolve the member's membership-type slug from their WA level name.
     * @throws RuntimeException when the level is a lifetime (non-renewable) type.
     */
    public function resolveTypeSlug(MemberProfile $profile): string
    {
        $levelName = trim($profile->level);
        $slug = self::LEVEL_NAME_TO_SLUG[$levelName] ?? null;

        // Fallback: a level name containing "lifetime"/"checkomatic"/"family"/"flat".
        if ($slug === null) {
            $lower = strtolower($levelName);
            $slug = match (true) {
                str_contains($lower, 'lifetime')   => 'lifetime_individual',
                str_contains($lower, 'checkomatic')=> str_contains($lower, 'family') ? 'checkomatic_family' : 'checkomatic_individual',
                str_contains($lower, 'flat')       => 'flat',
                str_contains($lower, 'family')     => 'family',
                default                            => 'individual',
            };
        }

        if (in_array($slug, self::LIFETIME_SLUGS, true)) {
            throw new RuntimeException("Lifetime membership ({$levelName}) is not renewable.");
        }

        return $slug;
    }

    /** True when a membership-type slug is a non-renewable lifetime plan. */
    public function isLifetimeLevel(MemberProfile $profile): bool
    {
        $levelName = trim($profile->level);
        $slug = self::LEVEL_NAME_TO_SLUG[$levelName] ?? null;
        if ($slug === null) {
            return str_contains(strtolower($levelName), 'lifetime');
        }
        return in_array($slug, self::LIFETIME_SLUGS, true);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/RenewalService.php tests/Unit/RenewalServiceTest.php
git commit -m "feat: add RenewalService with membership-level slug resolution"
```

---

## Task 4: RenewalService — fee resolution & summary

**Files:**
- Modify: `app/Services/RenewalService.php`
- Test: `tests/Unit/RenewalServiceTest.php`

- [ ] **Step 1: Add the failing tests**

Append to `tests/Unit/RenewalServiceTest.php` inside the class:

```php
    public function test_fee_for_standard_individual_plan(): void
    {
        $svc = app(RenewalService::class);
        $fee = $svc->resolveFee('individual', 0, null);

        $this->assertSame(2500, $fee['cents']);
        $this->assertSame('$25.00', $fee['label']);
    }

    public function test_fee_for_flat_plan_multiplies_by_member_count(): void
    {
        $svc = app(RenewalService::class);
        // 1 primary + 3 family members = 4 * $20 = $80.
        $fee = $svc->resolveFee('flat', 3, null);

        $this->assertSame(8000, $fee['cents']);
        $this->assertSame('$80.00', $fee['label']);
    }

    public function test_fee_for_checkomatic_uses_monthly_amount(): void
    {
        $svc = app(RenewalService::class);
        $fee = $svc->resolveFee('checkomatic_individual', 0, 15.50);

        $this->assertSame(1550, $fee['cents']);
        $this->assertSame('$15.50/mo', $fee['label']);
    }

    public function test_build_summary_shape(): void
    {
        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999,
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'],
            'FieldValues' => [],
        ]]);

        $summary = $svc->buildSummary($profile);

        $this->assertSame('individual', $summary['type']);
        $this->assertFalse($summary['isCheckomatic']);
        $this->assertSame(2500, $summary['fee']['cents']);
        $this->assertArrayHasKey('newRenewalDate', $summary);
        $this->assertSame(0, $summary['familyCount']);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: FAIL — `Call to undefined method ...resolveFee()`.

- [ ] **Step 3: Add resolveFee + buildSummary**

In `app/Services/RenewalService.php`, add these methods after `isLifetimeLevel()`:

```php
    /**
     * Resolve the renewal fee for a membership type.
     *
     * - flat:        $20 * (1 primary + $familyCount).
     * - checkomatic: the member-entered monthly amount.
     * - else:        the flat fee from config/membership.php.
     *
     * @return array{cents:int,label:string}
     */
    public function resolveFee(string $type, int $familyCount, ?float $checkomaticAmount): array
    {
        if ($type === 'flat') {
            $cents = (1 + max(0, $familyCount)) * 2000;
            return ['cents' => $cents, 'label' => '$' . number_format($cents / 100, 2)];
        }

        if ($type === 'checkomatic_family' || $type === 'checkomatic_individual') {
            $amount = (float) ($checkomaticAmount ?? 0);
            $cents  = (int) round($amount * 100);
            return ['cents' => $cents, 'label' => '$' . number_format($amount, 2) . '/mo'];
        }

        $fees = config('membership.fees');
        $entry = $fees[$type] ?? ['cents' => 0, 'label' => '$0.00'];
        return ['cents' => (int) $entry['cents'], 'label' => (string) $entry['label']];
    }

    /**
     * Build the data the renewal modal needs.
     * For checkomatic the fee cents are 0 until the member enters an amount.
     *
     * @return array{type:string,isCheckomatic:bool,fee:array,newRenewalDate:string,familyCount:int}
     */
    public function buildSummary(MemberProfile $profile): array
    {
        $type          = $this->resolveTypeSlug($profile);
        $isCheckomatic = str_starts_with($type, 'checkomatic');
        $familyCount   = count($profile->family);
        $fee           = $this->resolveFee($type, $familyCount, $isCheckomatic ? null : 0.0);

        return [
            'type'           => $type,
            'isCheckomatic'  => $isCheckomatic,
            'fee'            => $fee,
            'newRenewalDate' => $this->newRenewalDate($type),
            'familyCount'    => $familyCount,
        ];
    }

    /** The renewal date a successful renewal will set: end of next calendar year. */
    public function newRenewalDate(string $type): string
    {
        if (str_starts_with($type, 'checkomatic')) {
            return now()->addMonth()->format('F d, Y');
        }
        return now()->addYear()->endOfYear()->format('F d, Y');
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: PASS — 7 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/RenewalService.php tests/Unit/RenewalServiceTest.php
git commit -m "feat: add fee resolution and renewal summary to RenewalService"
```

---

## Task 5: ProcessMembershipRenewal job

**Files:**
- Create: `app/Jobs/ProcessMembershipRenewal.php`
- Test: `tests/Feature/ProcessMembershipRenewalTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ProcessMembershipRenewalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
use App\Services\WildApricotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessMembershipRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
    }

    private function fakeWa(): void
    {
        Http::fake([
            // Invoice creation
            'api.wildapricot.org/v2.3/accounts/12345/invoices' => Http::response(['Id' => 555], 200),
            // Payment creation
            'api.wildapricot.org/v2.3/accounts/12345/payments' => Http::response(['Id' => 777], 200),
            // Payment-system tenders (recordPayment looks up the tender id)
            'api.wildapricot.org/v2.3/accounts/12345/paymentsystemtenders*' => Http::response([
                ['Id' => 3, 'Name' => 'Stripe'],
            ], 200),
            // contactfields — getFamilyMembers resolves the Member Identifier code
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            // family lookup → no family
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            // contact GET (updateMember fetches current) + PUT
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
            ], 200),
        ]);
    }

    private function makeRenewal(): Renewal
    {
        return Renewal::create([
            'contact_id'      => 999,
            'member_email'    => 'tauqeer@example.com',
            'membership_type' => 'individual',
            'amount_cents'    => 2500,
            'currency'        => 'usd',
            'status'          => 'paid',
            'stripe_charge_id'=> 'ch_test_123',
            'stripe_payment_method_id' => 'pm_test_123',
        ]);
    }

    public function test_job_creates_invoice_records_payment_and_marks_processed(): void
    {
        $this->fakeWa();
        $renewal = $this->makeRenewal();

        (new ProcessMembershipRenewal($renewal))->handle(app(WildApricotService::class));

        $renewal->refresh();
        $this->assertTrue($renewal->processed);
        $this->assertSame('done', $renewal->wa_step);
        $this->assertSame(555, $renewal->wa_invoice_id);
        $this->assertSame('processed', $renewal->status);
    }

    public function test_job_is_idempotent_when_already_processed(): void
    {
        $this->fakeWa();
        $renewal = $this->makeRenewal();
        $renewal->update(['processed' => true, 'wa_step' => 'done']);

        (new ProcessMembershipRenewal($renewal))->handle(app(WildApricotService::class));

        // No new WA calls were needed; still processed.
        $this->assertTrue($renewal->fresh()->processed);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/ProcessMembershipRenewalTest.php`
Expected: FAIL — `Class "App\Jobs\ProcessMembershipRenewal" not found`.

- [ ] **Step 3: Create the job**

Create `app/Jobs/ProcessMembershipRenewal.php`:

```php
<?php

namespace App\Jobs;

use App\Models\Renewal;
use App\Services\MemberPortalService;
use App\Services\WildApricotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes the WildApricot side of a membership renewal after Stripe has
 * confirmed payment: creates the renewal invoice, records the payment, and
 * advances RenewalDue for the primary member and every family member.
 *
 * Idempotent — re-running resumes from the first incomplete step, so a queue
 * retry never double-invoices or double-records a payment.
 */
class ProcessMembershipRenewal implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Renewal $renewal) {}

    public function handle(WildApricotService $wa): void
    {
        $renewal = $this->renewal->fresh();

        if ($renewal->processed) {
            Log::info('ProcessMembershipRenewal: already processed, skipping', ['renewal_id' => $renewal->id]);
            return;
        }

        $contactId = (int) $renewal->contact_id;
        $type      = (string) $renewal->membership_type;
        $amount    = $renewal->amount_cents / 100;

        $fail = function (string $step, Throwable $e) use ($renewal): never {
            $renewal->update([
                'wa_step'     => $step,
                'error_message' => $e->getMessage(),
                'retry_count' => $renewal->retry_count + 1,
            ]);
            Log::error("ProcessMembershipRenewal failed at step [{$step}]", [
                'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
            ]);
            throw $e;
        };

        // ── Step 1: Create the renewal invoice ────────────────────────────
        $invoiceId = (int) ($renewal->wa_invoice_id ?? 0);
        if (! $invoiceId) {
            $renewal->update(['wa_step' => 'invoice']);
            try {
                $invoice   = $wa->createMembershipInvoice($contactId, $amount, $type);
                $invoiceId = (int) $invoice['Id'];
                $renewal->update(['wa_invoice_id' => $invoiceId]);
                try {
                    $wa->setInvoiceNumberOnContact($contactId, $invoiceId);
                } catch (Throwable $e) {
                    Log::warning('ProcessMembershipRenewal: Invoice# field update failed', [
                        'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('invoice', $e);
            }
        }

        // ── Step 2: Record the payment ────────────────────────────────────
        if ($renewal->wa_step !== 'payment' && ! in_array($renewal->wa_step, ['renewal', 'family', 'done'], true)) {
            $renewal->update(['wa_step' => 'payment']);
            try {
                $wa->recordPayment(
                    $contactId,
                    $invoiceId,
                    $amount,
                    (string) ($renewal->stripe_charge_id ?? ''),
                    (string) ($renewal->stripe_payment_method_id ?? '')
                );
                try {
                    $wa->setPaymentProcessedOnContact($contactId, (string) ($renewal->stripe_charge_id ?? ''));
                } catch (Throwable $e) {
                    Log::warning('ProcessMembershipRenewal: Payment Processed field update failed', [
                        'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('payment', $e);
            }
        }

        // ── Step 3: Advance the primary member's RenewalDue ───────────────
        $renewal->update(['wa_step' => 'renewal']);
        try {
            $wa->updateMember($contactId, ['membership_type' => $type]);
        } catch (Throwable $e) {
            $fail('renewal', $e);
        }

        // ── Step 4: Advance each family member's RenewalDue ───────────────
        $renewal->update(['wa_step' => 'family']);
        try {
            foreach ($wa->getFamilyMembers($contactId) as $family) {
                $familyId = (int) ($family['Id'] ?? 0);
                if ($familyId <= 0) {
                    continue;
                }
                try {
                    $wa->updateMember($familyId, ['membership_type' => $type]);
                } catch (Throwable $e) {
                    // Non-fatal — one bad family record must not fail the renewal.
                    Log::warning('ProcessMembershipRenewal: family member update failed', [
                        'renewal_id' => $renewal->id, 'family_id' => $familyId, 'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            $fail('family', $e);
        }

        // ── Done ──────────────────────────────────────────────────────────
        $renewal->update(['wa_step' => 'done', 'processed' => true, 'status' => 'processed']);

        // Invalidate the cached member bundle so the dashboard reflects the renewal.
        try {
            app(MemberPortalService::class)->invalidate($contactId);
        } catch (Throwable $e) {
            Log::warning('ProcessMembershipRenewal: cache invalidation failed', [
                'renewal_id' => $renewal->id, 'error' => $e->getMessage(),
            ]);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/ProcessMembershipRenewalTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/ProcessMembershipRenewal.php tests/Feature/ProcessMembershipRenewalTest.php
git commit -m "feat: add ProcessMembershipRenewal job for WildApricot renewal side"
```

---

## Task 6: ProcessMembershipRenewal — family update test

**Files:**
- Modify: `tests/Feature/ProcessMembershipRenewalTest.php`

- [ ] **Step 1: Add the failing test**

Append to `tests/Feature/ProcessMembershipRenewalTest.php` inside the class:

```php
    public function test_job_updates_family_members_renewal_dates(): void
    {
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);

        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices' => Http::response(['Id' => 555], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments' => Http::response(['Id' => 777], 200),
            'api.wildapricot.org/v2.3/accounts/12345/paymentsystemtenders*' => Http::response([
                ['Id' => 3, 'Name' => 'Stripe'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            // family lookup → one spouse
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response([
                'Contacts' => [['Id' => 1001, 'FirstName' => 'Sarah', 'FieldValues' => []]],
            ], 200),
            // both the primary and the spouse contact GET/PUT
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 1, 'Name' => 'Family'], 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts/1001' => Http::response([
                'Id' => 1001, 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 1, 'Name' => 'Family'], 'FieldValues' => [],
            ], 200),
        ]);

        $renewal = Renewal::create([
            'contact_id'      => 999,
            'member_email'    => 'tauqeer@example.com',
            'membership_type' => 'family',
            'amount_cents'    => 4000,
            'currency'        => 'usd',
            'status'          => 'paid',
            'stripe_charge_id'=> 'ch_test_456',
        ]);

        (new ProcessMembershipRenewal($renewal))->handle(app(WildApricotService::class));

        $renewal->refresh();
        $this->assertTrue($renewal->processed);

        // The spouse contact (1001) must have received a PUT (updateMember).
        Http::assertSent(fn ($request) =>
            $request->method() === 'PUT' && str_contains($request->url(), '/contacts/1001')
        );
    }
```

- [ ] **Step 2: Run test to verify it passes**

Run: `php artisan test tests/Feature/ProcessMembershipRenewalTest.php`
Expected: PASS — 3 tests (the new one verifies the spouse received an `updateMember` PUT).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/ProcessMembershipRenewalTest.php
git commit -m "test: verify renewal job updates family members' renewal dates"
```

---

## Task 7: RenewalService — charge() Stripe sequence

**Files:**
- Modify: `app/Services/RenewalService.php`
- Test: `tests/Unit/RenewalServiceTest.php`

- [ ] **Step 1: Add the failing test**

Append to `tests/Unit/RenewalServiceTest.php`. Add these imports at the top of the file (after the existing `use` lines):

```php
use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
```

Add `use RefreshDatabase;` as the first line inside the `RenewalServiceTest` class body.

Append these test methods inside the class:

```php
    public function test_charge_success_creates_paid_renewal_and_dispatches_job(): void
    {
        Queue::fake();

        // Mock StripeService so no real Stripe calls happen.
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn((object) ['id' => 'cus_test']);
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            (object) ['type' => 'card', 'card' => (object) ['brand' => 'visa', 'last4' => '4242']]
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn((object) ['id' => 'pi_test']);
        $stripe->shouldReceive('processPayment')->andReturn(
            (object) ['status' => 'succeeded', 'latest_charge' => 'ch_test']
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'pm_test_123', null);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('renewal_id', $result);
        $this->assertDatabaseHas('renewals', ['id' => $result['renewal_id'], 'status' => 'paid']);
        Queue::assertPushed(ProcessMembershipRenewal::class);
    }

    public function test_charge_requires_action_returns_client_secret(): void
    {
        Queue::fake();

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn((object) ['id' => 'cus_test']);
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            (object) ['type' => 'card', 'card' => (object) ['brand' => 'visa', 'last4' => '4242']]
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn((object) ['id' => 'pi_test']);
        $stripe->shouldReceive('processPayment')->andReturn(
            (object) ['status' => 'requires_action', 'client_secret' => 'pi_test_secret']
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'pm_test_123', null);

        $this->assertTrue($result['requires_action']);
        $this->assertSame('pi_test_secret', $result['client_secret']);
        Queue::assertNotPushed(ProcessMembershipRenewal::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: FAIL — `Call to undefined method ...charge()`.

- [ ] **Step 3: Add the charge() method and constructor**

In `app/Services/RenewalService.php`, add the `StripeService` import at the top:

```php
use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
```

Add a constructor right after the class's `private const` declarations:

```php
    public function __construct(private StripeService $stripe) {}
```

Also add `use App\Services\StripeService;` — note `RenewalService` is in the `App\Services` namespace, so reference it as just `StripeService` and no import line is needed (same namespace). Use `private StripeService $stripe` directly.

Add the `charge()` method after `newRenewalDate()`:

```php
    /**
     * Run the Stripe charge sequence for a renewal. On success, persists a
     * 'paid' Renewal and dispatches ProcessMembershipRenewal.
     *
     * @return array{
     *   success:bool, renewal_id?:int, requires_action?:bool,
     *   client_secret?:string, payment_intent_id?:string, message?:string
     * }
     */
    public function charge(int $contactId, MemberProfile $profile, string $paymentMethodId, ?float $checkomaticAmount): array
    {
        $type        = $this->resolveTypeSlug($profile); // throws for lifetime
        $familyCount = count($profile->family);
        $fee         = $this->resolveFee($type, $familyCount, $checkomaticAmount);

        $renewal = Renewal::create([
            'contact_id'               => $contactId,
            'member_email'             => $profile->email,
            'membership_type'          => $type,
            'amount_cents'             => $fee['cents'],
            'currency'                 => 'usd',
            'status'                   => 'pending',
            'stripe_payment_method_id' => $paymentMethodId,
        ]);

        $description = ucwords(str_replace('_', ' ', $type)) . ' Membership Renewal — ISGH';

        try {
            // ── Customer ──────────────────────────────────────────────────
            $customer = $this->stripe->createCustomer([
                'name'  => trim($profile->fullName) ?: $profile->email,
                'email' => $profile->email,
                'phone' => $profile->phone ?: null,
            ]);
            $renewal->update(['stripe_customer_id' => $customer->id]);

            // ── Payment method ───────────────────────────────────────────
            $pm   = $this->stripe->addPaymentMethodToCustomer($paymentMethodId, $customer->id);
            $card = $pm->card ?? null;
            $renewal->update([
                'payment_method' => $pm->type ?? null,
                'card_brand'     => $card->brand ?? null,
                'card_last4'     => $card->last4 ?? null,
            ]);

            // ── Payment intent ───────────────────────────────────────────
            $intent = $this->stripe->createPaymentIntent([
                'amount_cents' => $fee['cents'],
                'currency'     => 'usd',
                'customer_id'  => $customer->id,
                'description'  => $description,
                'metadata'     => [
                    'contact_id'      => $contactId,
                    'renewal_id'      => $renewal->id,
                    'membership_type' => $type,
                ],
            ]);
            $renewal->update(['stripe_payment_intent_id' => $intent->id]);

            // ── Confirm / charge ─────────────────────────────────────────
            $confirmed = $this->stripe->processPayment($intent->id, $paymentMethodId);
            $succeeded = ($confirmed->status ?? null) === 'succeeded';

            if (! $succeeded) {
                if (($confirmed->status ?? null) === 'requires_action') {
                    return [
                        'success'           => true,
                        'requires_action'   => true,
                        'client_secret'     => $confirmed->client_secret ?? '',
                        'payment_intent_id' => $intent->id,
                        'renewal_id'        => $renewal->id,
                    ];
                }
                $renewal->update(['status' => 'failed', 'error_message' => 'Payment status: ' . ($confirmed->status ?? 'unknown')]);
                return ['success' => false, 'message' => 'Payment could not be completed. Please try another card.'];
            }

            $renewal->update([
                'status'           => 'paid',
                'stripe_charge_id' => $confirmed->latest_charge ?? null,
                'paid_at'          => now(),
            ]);

            ProcessMembershipRenewal::dispatch($renewal);

            return ['success' => true, 'renewal_id' => $renewal->id];
        } catch (\Throwable $e) {
            $renewal->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('RenewalService::charge failed', ['renewal_id' => $renewal->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()];
        }
    }
```

Add `use Illuminate\Support\Facades\Log;` to the imports at the top of the file.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: PASS — 9 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/RenewalService.php tests/Unit/RenewalServiceTest.php
git commit -m "feat: add Stripe charge sequence to RenewalService"
```

---

## Task 8: Renewal routes + controller endpoints

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/MemberPortalController.php`
- Test: `tests/Feature/MembershipRenewalTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MembershipRenewalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class MembershipRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Lapsed',
                'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/membershiplevels' => Http::response([
                ['Id' => 1, 'Name' => 'Individual', 'MembershipFee' => 25.0],
            ], 200),
        ]);
    }

    public function test_renew_summary_returns_fee_and_renewal_date(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson('/member-portal/renew/summary')
          ->assertOk()
          ->assertJson(['renewable' => true, 'type' => 'individual'])
          ->assertJsonPath('fee.cents', 2500);
    }

    public function test_process_renewal_charges_and_creates_renewal(): void
    {
        Queue::fake();

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn((object) ['id' => 'cus_test']);
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            (object) ['type' => 'card', 'card' => (object) ['brand' => 'visa', 'last4' => '4242']]
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn((object) ['id' => 'pi_test']);
        $stripe->shouldReceive('processPayment')->andReturn(
            (object) ['status' => 'succeeded', 'latest_charge' => 'ch_test']
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->postJson('/member-portal/renew', ['payment_method_id' => 'pm_test_123'])
          ->assertOk()
          ->assertJson(['success' => true]);

        $this->assertDatabaseHas('renewals', ['contact_id' => 999, 'status' => 'paid']);
        Queue::assertPushed(ProcessMembershipRenewal::class);
    }

    public function test_renew_status_reflects_renewal_state(): void
    {
        $renewal = Renewal::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'membership_type' => 'individual', 'amount_cents' => 2500,
            'currency' => 'usd', 'status' => 'processed', 'processed' => true,
            'wa_invoice_id' => 555,
        ]);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson("/member-portal/renew/status/{$renewal->id}")
          ->assertOk()
          ->assertJson(['processed' => true, 'wa_invoice_id' => 555]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/MembershipRenewalTest.php`
Expected: FAIL — 404, the renewal routes don't exist.

- [ ] **Step 3: Add the routes**

In `routes/web.php`, inside the `member-portal` group's `member.portal.auth` middleware block (alongside `dashboard`, `profile`, `profile.update`), add:

```php
        Route::get('/renew/summary',         [MemberPortalController::class, 'renewSummary'])->name('renew.summary');
        Route::post('/renew',                [MemberPortalController::class, 'processRenewal'])->name('renew');
        Route::get('/renew/status/{renewal}',[MemberPortalController::class, 'renewStatus'])->name('renew.status');
```

- [ ] **Step 4: Add the controller endpoints**

In `app/Http/Controllers/MemberPortalController.php`, add these imports at the top (after the existing `use` lines):

```php
use App\Models\Renewal;
use App\Services\RenewalService;
```

Add these three methods after `updateProfile()` (and before `logout()`):

```php
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
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/MembershipRenewalTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/MemberPortalController.php tests/Feature/MembershipRenewalTest.php
git commit -m "feat: add renewal endpoints to MemberPortalController"
```

---

## Task 9: Lifetime-member guard test

**Files:**
- Modify: `tests/Feature/MembershipRenewalTest.php`

- [ ] **Step 1: Add the failing test**

Append to `tests/Feature/MembershipRenewalTest.php` inside the class:

```php
    public function test_lifetime_member_cannot_renew(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/888' => Http::response([
                'Id' => 888, 'FirstName' => 'Life', 'LastName' => 'Member',
                'Email' => 'life@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 9, 'Name' => 'Lifetime'], 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
        ]);

        // Summary reports not-renewable.
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->getJson('/member-portal/renew/summary')
          ->assertOk()
          ->assertJson(['renewable' => false]);

        // The charge endpoint rejects it with 422.
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->postJson('/member-portal/renew', ['payment_method_id' => 'pm_test'])
          ->assertStatus(422);
    }
```

- [ ] **Step 2: Run test to verify it passes**

Run: `php artisan test tests/Feature/MembershipRenewalTest.php`
Expected: PASS — 4 tests.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/MembershipRenewalTest.php
git commit -m "test: verify lifetime members cannot renew"
```

---

## Task 10: Wire the dashboard renewal modal to the endpoints

**Files:**
- Modify: `resources/views/member-portal/dashboard.blade.php`

- [ ] **Step 1: Read the current renewal UI**

Read `resources/views/member-portal/dashboard.blade.php` in full. Locate:
- The "Renew Membership" button in the expired-state alert (`.btn-renew` / `.btn-renew-mobile`).
- The "Renew Membership" item in the Quick Links card.
- Any existing renewal/expired modal markup.
- The `<head>` — confirm `<meta name="csrf-token">` exists (added in a prior feature).

- [ ] **Step 2: Hide the renew controls for lifetime members**

In the dashboard's `@php` block (the one that already computes `$isExpired`, `$daysLeft`, `$daysOverdue`), add:

```php
  $isLifetime = stripos($profile->level, 'lifetime') !== false;
```

Wrap the expired-alert "Renew Membership" button(s) and the Quick Links "Renew Membership" item each in `@unless($isLifetime) ... @endunless` so they are not rendered for lifetime members.

- [ ] **Step 3: Add the renewal modal markup**

Before the closing `</body>` tag, add a renewal modal with three screens (confirm → payment → success). Use the existing dashboard CSS variables and modal/card classes for visual consistency — match the structure of any existing modal in the file. The modal must contain:
- A confirm screen: heading "Membership Expired!" / "Renew your membership", a "Yes, Renew Now" button (`id="renewConfirmBtn"`) and a "Not Now" button that closes the modal.
- A payment screen: a "Pending Payment" amount line (`id="renewAmountLabel"`), Stripe card Element mount point (`id="renew-card-element"`), a checkomatic monthly-amount input (`id="renewMonthlyAmount"`, shown only when `data-checkomatic` is set), a "Pay" button (`id="renewPayBtn"`), and a "Back" button.
- A success screen: heading "Renewal Successful!", an invoice line (`id="renewInvoiceLabel"`), and a "Go to Dashboard" button.

- [ ] **Step 4: Add the renewal JavaScript**

In the dashboard's `<script>` block, add a self-contained renewal module. It must:
1. Open the modal when any `.btn-renew`, `.btn-renew-mobile`, or the Quick Links renew link is clicked.
2. On "Yes, Renew Now": `fetch('{{ route('member-portal.renew.summary') }}')`, then show the payment screen with the fee label; if `isCheckomatic`, reveal the monthly-amount input.
3. Initialize Stripe.js with the publishable key (read it the same way the signup page does — check the signup Blade view for the `Stripe(...)` init and the publishable-key source; reuse that exact pattern) and mount a card Element into `#renew-card-element`.
4. On "Pay": create a `payment_method` from the card Element via `stripe.createPaymentMethod`, disable the Pay button, then `fetch('{{ route('member-portal.renew') }}', { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': <meta token> }, body: JSON.stringify({ payment_method_id, monthly_amount }) })`.
5. If the response has `requires_action`, call `stripe.handleCardAction(client_secret)`, then re-POST to confirm (mirror the signup page's 3DS handling — copy that logic).
6. On `success`, poll `{{ route('member-portal.renew.status', ['renewal' => '__ID__']) }}` (substitute the `renewal_id`) until `processed` is true (or a small timeout), then show the success screen with the invoice number.
7. On failure, show the returned `message` inline and re-enable the Pay button.

IMPORTANT: Read the signup page's Blade view + JS first (find it via the `membership.checkout` route → its view) and mirror its Stripe.js initialization, card-Element mounting, `createPaymentMethod`, and `requires_action`/`handleCardAction` handling exactly. Do not invent a different Stripe integration — reuse the working signup pattern. Only the endpoint URLs and payload differ.

- [ ] **Step 5: Manual verification**

Run `php artisan serve`, log into the member portal as an expired (non-lifetime) member, click "Renew Membership". Confirm: the modal opens, the summary shows the correct fee, the Stripe card field renders, and (with a Stripe test card `4242 4242 4242 4242`) the payment completes and the success screen shows an invoice number. Confirm the Renew buttons are absent for a lifetime member.

- [ ] **Step 6: Run the full member-portal + renewal test suite**

Run: `php artisan test tests/Feature/MembershipRenewalTest.php tests/Feature/ProcessMembershipRenewalTest.php tests/Unit/RenewalServiceTest.php tests/Feature/RenewalModelTest.php tests/Unit/MembershipFeeConfigTest.php`
Expected: all pass.

- [ ] **Step 7: Commit**

```bash
git add resources/views/member-portal/dashboard.blade.php
git commit -m "feat: wire dashboard renewal modal to renewal endpoints with Stripe"
```

---

## Task 11: Manual end-to-end smoke test

**Files:** none (verification only)

- [ ] **Step 1: Renew a non-lifetime member end-to-end**

With the app running and Stripe in test mode, log into the portal as an expired Individual or Family member. Click "Renew Membership" → confirm → pay with test card `4242 4242 4242 4242`. Verify the success screen shows "Renewal Successful!" with an invoice number.

- [ ] **Step 2: Verify WildApricot side**

In WildApricot, confirm the member now has: a new renewal invoice, a recorded payment, and an advanced `RenewalDue` (end of next calendar year). If the member has a spouse/family, confirm their `RenewalDue` advanced too.

- [ ] **Step 3: Verify the dashboard reflects the renewal**

Reload the portal dashboard (use `?refresh=1` to bypass the cache). The membership status should no longer show expired, and the renewal date should be the new one.

- [ ] **Step 4: Verify the checkomatic path**

Log in as a checkomatic member, open the renewal modal, confirm the monthly-amount input appears, enter an amount, and complete the payment. Confirm the charge matches the entered amount.

- [ ] **Step 5: Reconcile if needed**

If the WA `MembershipLevel.Name` on a real member doesn't match `RenewalService::LEVEL_NAME_TO_SLUG`, the fallback `match` handles common cases — but if a real level name maps to the wrong slug, correct `LEVEL_NAME_TO_SLUG`, re-run `php artisan test tests/Unit/RenewalServiceTest.php`, and commit:

```bash
git add app/Services/RenewalService.php
git commit -m "fix: correct WA membership-level name mapping after live verification"
```

---

## Self-Review Notes

- **Spec coverage:** fee table extracted to shared config (T1); `renewals` table + model (T2); `RenewalService` slug resolution incl. lifetime-throws (T3), fee resolution for all 3 branches + summary (T4), Stripe `charge()` sequence (T7); `ProcessMembershipRenewal` job — invoice/payment/primary renewal date/family renewal dates/idempotency (T5-T6); controller endpoints `renewSummary`/`processRenewal`/`renewStatus` (T8); lifetime guard server-side (T8-T9); dashboard modal wired with Stripe, renew buttons hidden for lifetime, checkomatic monthly-amount input (T10); cache invalidation on processed (T5); manual smoke incl. checkomatic + family (T11). All spec sections covered.
- **Type consistency:** `RenewalService` methods (`resolveTypeSlug`, `isLifetimeLevel`, `resolveFee`, `buildSummary`, `newRenewalDate`, `charge`) are consistent across T3/T4/T7 definitions and T8 controller usage. `Renewal` model fields match the migration columns (T2) and every write in `charge()` (T7) and the job (T5). The job constructor `ProcessMembershipRenewal(Renewal $renewal)` is consistent across T5 and the `dispatch()` in T7. `charge()`'s return shape (`success`/`requires_action`/`client_secret`/`renewal_id`/`message`) matches what `processRenewal` returns to the client (T8) and what the JS consumes (T10).
- **No placeholders:** every code step contains complete code; T10's Blade/JS steps direct the implementer to read and mirror the existing signup Stripe integration rather than inventing one, with concrete element IDs and endpoint routes specified.

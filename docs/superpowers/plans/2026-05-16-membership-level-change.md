# Membership Level Change Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a member change their membership level from the portal — pick a new plan, add spouse/family members if the new plan includes them, pay the new plan's fee via Stripe, and have WildApricot updated (level switched, invoice + payment recorded, new family members created).

**Architecture:** Shared `MembershipTypes`/`MembershipFee` support classes hold the level-name↔slug map and fee resolver (extracted from `RenewalService`, which is refactored to delegate). A `LevelChangeService` resolves the target-level fee and runs the Stripe charge; on success it dispatches a queued `ProcessLevelChange` job that does the WildApricot side (invoice, payment, level switch, family-contact creation). A `level_changes` table gives the job durable, retryable state. `MemberPortalController` exposes thin endpoints; the dashboard "Change Level" link opens a multi-step modal.

**Tech Stack:** Laravel 12, PHP 8.2, Stripe PHP SDK, WildApricot REST API v2.3, Blade, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-05-16-membership-level-change-design.md`

---

## File Structure

**Create:**
- `app/Support/MembershipTypes.php` — level-name↔slug map + type helpers.
- `app/Support/MembershipFee.php` — fee resolver (`{cents,label}`).
- `app/Services/LevelChangeService.php` — orchestrator (options, fee, charge, finalize).
- `app/Jobs/ProcessLevelChange.php` — queued job for the WildApricot side.
- `database/migrations/2026_05_16_100000_create_level_changes_table.php` + `app/Models/LevelChange.php`.
- `tests/Unit/MembershipTypesTest.php`, `tests/Unit/MembershipFeeTest.php`,
  `tests/Unit/LevelChangeServiceTest.php`,
  `tests/Feature/ProcessLevelChangeTest.php`, `tests/Feature/MembershipLevelChangeTest.php`.

**Modify:**
- `app/Services/RenewalService.php` — delegate `resolveFee` + the level-name↔slug map to the shared support classes (outputs unchanged).
- `app/Http/Controllers/MemberPortalController.php` — add `changeLevelOptions`, `processLevelChange`, `finalizeLevelChange`, `levelChangeStatus`.
- `routes/web.php` — 4 `change-level` routes.
- `resources/views/member-portal/dashboard.blade.php` — wire the "Change Level" Quick Links item to a multi-step modal.

---

## Task 1: MembershipTypes support class

**Files:**
- Create: `app/Support/MembershipTypes.php`
- Test: `tests/Unit/MembershipTypesTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/MembershipTypesTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Support\MembershipTypes;
use Tests\TestCase;

class MembershipTypesTest extends TestCase
{
    public function test_slug_from_level_name_maps_known_names(): void
    {
        $this->assertSame('individual', MembershipTypes::slugFromLevelName('Individual'));
        $this->assertSame('family', MembershipTypes::slugFromLevelName('Family Membership (Primary and Spouse only)'));
        $this->assertSame('flat', MembershipTypes::slugFromLevelName('Flat Membership'));
    }

    public function test_slug_from_level_name_falls_back_on_unknown(): void
    {
        $this->assertSame('checkomatic_family', MembershipTypes::slugFromLevelName('Checkomatic Family Plan 2026'));
        $this->assertSame('lifetime_individual', MembershipTypes::slugFromLevelName('Some Lifetime Tier'));
        $this->assertSame('individual', MembershipTypes::slugFromLevelName('Totally Unknown'));
    }

    public function test_includes_family(): void
    {
        foreach (['family', 'checkomatic_family', 'lifetime_family', 'flat'] as $slug) {
            $this->assertTrue(MembershipTypes::includesFamily($slug), "$slug includes family");
        }
        foreach (['individual', 'checkomatic_individual', 'lifetime_individual'] as $slug) {
            $this->assertFalse(MembershipTypes::includesFamily($slug), "$slug excludes family");
        }
    }

    public function test_is_checkomatic_and_is_lifetime(): void
    {
        $this->assertTrue(MembershipTypes::isCheckomatic('checkomatic_individual'));
        $this->assertFalse(MembershipTypes::isCheckomatic('family'));
        $this->assertTrue(MembershipTypes::isLifetime('lifetime_family'));
        $this->assertFalse(MembershipTypes::isLifetime('individual'));
    }

    public function test_label_for_slug(): void
    {
        $this->assertSame('Family Membership', MembershipTypes::labelForSlug('family'));
        $this->assertSame('Individual Membership', MembershipTypes::labelForSlug('individual'));
        $this->assertSame('Checkomatic Family', MembershipTypes::labelForSlug('checkomatic_family'));
    }

    public function test_all_slugs(): void
    {
        $this->assertCount(7, MembershipTypes::allSlugs());
        $this->assertContains('individual', MembershipTypes::allSlugs());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/MembershipTypesTest.php`
Expected: FAIL — `Class "App\Support\MembershipTypes" not found`.

- [ ] **Step 3: Create the class**

Create `app/Support/MembershipTypes.php`:

```php
<?php

namespace App\Support;

/**
 * Shared membership-type knowledge: the WildApricot level-name -> slug map,
 * type classification helpers, and human labels. Used by RenewalService and
 * LevelChangeService so the mapping lives in exactly one place.
 */
class MembershipTypes
{
    /** WildApricot membership-level NAME => membership-type slug. */
    public const LEVEL_NAME_TO_SLUG = [
        'Family Membership (Primary and Spouse only)'      => 'family',
        'Individual'                                       => 'individual',
        'Flat Membership'                                  => 'flat',
        'Checkomatic Membership (Primary and Spouse only)' => 'checkomatic_family',
        'Checkomatic'                                      => 'checkomatic_individual',
        'Lifetime'                                         => 'lifetime_individual',
    ];

    /** Human-readable label for each slug. */
    public const SLUG_LABELS = [
        'family'                 => 'Family Membership',
        'individual'             => 'Individual Membership',
        'flat'                   => 'Flat Membership',
        'checkomatic_family'     => 'Checkomatic Family',
        'checkomatic_individual' => 'Checkomatic Individual',
        'lifetime_family'        => 'Lifetime Family',
        'lifetime_individual'    => 'Lifetime Individual',
    ];

    private const LIFETIME_SLUGS = ['lifetime_family', 'lifetime_individual'];
    private const FAMILY_SLUGS   = ['family', 'checkomatic_family', 'lifetime_family', 'flat'];

    /** All 7 membership-type slugs. */
    public static function allSlugs(): array
    {
        return array_keys(self::SLUG_LABELS);
    }

    /**
     * Map a WildApricot membership-level name to a membership-type slug.
     * Uses the LEVEL_NAME_TO_SLUG table, with a substring fallback.
     */
    public static function slugFromLevelName(string $levelName): string
    {
        $levelName = trim($levelName);
        $slug = self::LEVEL_NAME_TO_SLUG[$levelName] ?? null;
        if ($slug !== null) {
            return $slug;
        }

        $lower = strtolower($levelName);
        return match (true) {
            str_contains($lower, 'lifetime')    => 'lifetime_individual',
            str_contains($lower, 'checkomatic') => str_contains($lower, 'family') ? 'checkomatic_family' : 'checkomatic_individual',
            str_contains($lower, 'flat')        => 'flat',
            str_contains($lower, 'family')      => 'family',
            default                             => 'individual',
        };
    }

    /** Human label for a slug, or the slug itself if unknown. */
    public static function labelForSlug(string $slug): string
    {
        return self::SLUG_LABELS[$slug] ?? $slug;
    }

    /** True when the slug is a lifetime plan. */
    public static function isLifetime(string $slug): bool
    {
        return in_array($slug, self::LIFETIME_SLUGS, true);
    }

    /** True when the slug is a checkomatic (recurring monthly) plan. */
    public static function isCheckomatic(string $slug): bool
    {
        return str_starts_with($slug, 'checkomatic');
    }

    /** True when the slug's plan includes a spouse/family. */
    public static function includesFamily(string $slug): bool
    {
        return in_array($slug, self::FAMILY_SLUGS, true);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/MembershipTypesTest.php`
Expected: PASS — 6 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Support/MembershipTypes.php tests/Unit/MembershipTypesTest.php
git commit -m "feat: add MembershipTypes support class for shared type knowledge"
```

---

## Task 2: MembershipFee support class

**Files:**
- Create: `app/Support/MembershipFee.php`
- Test: `tests/Unit/MembershipFeeTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/MembershipFeeTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Support\MembershipFee;
use Tests\TestCase;

class MembershipFeeTest extends TestCase
{
    public function test_standard_fee_from_config(): void
    {
        $fee = MembershipFee::resolve('individual', 0, null);
        $this->assertSame(2500, $fee['cents']);
        $this->assertSame('$25.00', $fee['label']);
    }

    public function test_family_fee_from_config(): void
    {
        $fee = MembershipFee::resolve('family', 0, null);
        $this->assertSame(4000, $fee['cents']);
    }

    public function test_flat_fee_multiplies_by_member_count(): void
    {
        // 1 primary + 3 family = 4 * $20.
        $fee = MembershipFee::resolve('flat', 3, null);
        $this->assertSame(8000, $fee['cents']);
        $this->assertSame('$80.00', $fee['label']);
    }

    public function test_checkomatic_fee_uses_monthly_amount(): void
    {
        $fee = MembershipFee::resolve('checkomatic_individual', 0, 15.50);
        $this->assertSame(1550, $fee['cents']);
        $this->assertSame('$15.50/mo', $fee['label']);
    }

    public function test_unknown_type_returns_zero(): void
    {
        $fee = MembershipFee::resolve('nonsense', 0, null);
        $this->assertSame(0, $fee['cents']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/MembershipFeeTest.php`
Expected: FAIL — `Class "App\Support\MembershipFee" not found`.

- [ ] **Step 3: Create the class**

Create `app/Support/MembershipFee.php`:

```php
<?php

namespace App\Support;

/**
 * Resolves a membership fee for a type. Shared by RenewalService and
 * LevelChangeService so the fee math lives in one place.
 *
 * - flat:        $20 * (1 primary + $familyCount).
 * - checkomatic: the member-entered monthly amount.
 * - else:        the flat fee from config/membership.php.
 */
class MembershipFee
{
    /** @return array{cents:int,label:string} */
    public static function resolve(string $type, int $familyCount, ?float $checkomaticAmount): array
    {
        if ($type === 'flat') {
            $perMember = (int) (config('membership.fees')['flat']['cents'] ?? 2000);
            $cents = (1 + max(0, $familyCount)) * $perMember;
            return ['cents' => $cents, 'label' => '$' . number_format($cents / 100, 2)];
        }

        if ($type === 'checkomatic_family' || $type === 'checkomatic_individual') {
            $amount = (float) ($checkomaticAmount ?? 0);
            $cents  = (int) round($amount * 100);
            return ['cents' => $cents, 'label' => '$' . number_format($amount, 2) . '/mo'];
        }

        $entry = config('membership.fees')[$type] ?? ['cents' => 0, 'label' => '$0.00'];
        return ['cents' => (int) $entry['cents'], 'label' => (string) $entry['label']];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/MembershipFeeTest.php`
Expected: PASS — 5 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Support/MembershipFee.php tests/Unit/MembershipFeeTest.php
git commit -m "feat: add MembershipFee support class for shared fee resolution"
```

---

## Task 3: Refactor RenewalService to delegate to the shared classes

**Files:**
- Modify: `app/Services/RenewalService.php`

- [ ] **Step 1: Confirm the renewal tests are green before the refactor**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: PASS — all renewal-service tests (this is the regression net for the refactor).

- [ ] **Step 2: Replace `resolveFee()`'s body with a delegation**

In `app/Services/RenewalService.php`, the `resolveFee()` method currently contains the full 3-branch fee logic. Replace its **body** (keep the signature and docblock) so it delegates:

```php
    public function resolveFee(string $type, int $familyCount, ?float $checkomaticAmount): array
    {
        return \App\Support\MembershipFee::resolve($type, $familyCount, $checkomaticAmount);
    }
```

Add `use App\Support\MembershipFee;` to the imports at the top of the file and use the short `MembershipFee::resolve(...)` form.

- [ ] **Step 3: Replace the level-name->slug logic with a delegation**

`RenewalService` has a private `resolveSlug(MemberProfile $profile): string` that contains the level-name->slug map + fallback, and a `private const LEVEL_NAME_TO_SLUG`. Replace the `resolveSlug()` body so it delegates to `MembershipTypes`, and delete the now-unused `LEVEL_NAME_TO_SLUG` constant:

```php
    private function resolveSlug(MemberProfile $profile): string
    {
        return \App\Support\MembershipTypes::slugFromLevelName($profile->level);
    }
```

Add `use App\Support\MembershipTypes;` and use the short form. DELETE the `private const LEVEL_NAME_TO_SLUG = [ ... ];` block (it is now in `MembershipTypes`). KEEP `private const LIFETIME_SLUGS` — `resolveTypeSlug()` and `isLifetimeLevel()` still use it (or, optionally, change those two to use `MembershipTypes::isLifetime()` — but keeping `LIFETIME_SLUGS` is the minimal change; do that).

The `MembershipTypes` map is identical to `RenewalService`'s old map and the fallback `match` is identical, so `resolveSlug()` produces the same output. `MembershipFee::resolve()` is the old `resolveFee()` body verbatim. No behavior change.

- [ ] **Step 4: Run the renewal tests to confirm no regression**

Run: `php artisan test tests/Unit/RenewalServiceTest.php`
Expected: PASS — exact same test count as Step 1. The delegation produces identical outputs.

- [ ] **Step 5: Commit**

```bash
git add app/Services/RenewalService.php
git commit -m "refactor: RenewalService delegates fee + slug logic to shared support classes"
```

---

## Task 4: level_changes table migration + LevelChange model

**Files:**
- Create: `database/migrations/2026_05_16_100000_create_level_changes_table.php`
- Create: `app/Models/LevelChange.php`
- Test: `tests/Feature/LevelChangeModelTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LevelChangeModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\LevelChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelChangeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_change_can_be_created(): void
    {
        $lc = LevelChange::create([
            'contact_id'      => 999,
            'member_email'    => 'tauqeer@example.com',
            'from_type'       => 'individual',
            'to_type'         => 'family',
            'amount_cents'    => 4000,
            'currency'        => 'usd',
            'status'          => 'pending',
            'family_members'  => [['first_name' => 'Sarah', 'last_name' => 'Alam']],
        ]);

        $this->assertDatabaseHas('level_changes', [
            'id'        => $lc->id,
            'from_type' => 'individual',
            'to_type'   => 'family',
        ]);
    }

    public function test_casts_json_and_boolean_columns(): void
    {
        $lc = LevelChange::create([
            'contact_id'         => 1,
            'from_type'          => 'individual',
            'to_type'            => 'family',
            'amount_cents'       => 4000,
            'currency'           => 'usd',
            'status'             => 'processed',
            'processed'          => 1,
            'family_members'     => [['first_name' => 'Sarah']],
            'created_family_ids' => [101],
        ]);

        $fresh = $lc->fresh();
        $this->assertIsBool($fresh->processed);
        $this->assertTrue($fresh->processed);
        $this->assertIsArray($fresh->family_members);
        $this->assertSame('Sarah', $fresh->family_members[0]['first_name']);
        $this->assertSame([101], $fresh->created_family_ids);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LevelChangeModelTest.php`
Expected: FAIL — `Class "App\Models\LevelChange" not found`.

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_05_16_100000_create_level_changes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('level_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');          // WildApricot contact id
            $table->string('member_email')->nullable();
            $table->string('from_type');
            $table->string('to_type');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 8)->default('usd');

            $table->json('family_members')->nullable();        // submitted spouse/family payload
            $table->json('created_family_ids')->nullable();    // WA ids of family contacts created so far

            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();

            $table->string('status')->default('pending');      // pending|paid|failed|processed
            $table->unsignedBigInteger('wa_invoice_id')->nullable();
            $table->unsignedBigInteger('wa_bundle_id')->nullable();
            $table->unsignedBigInteger('wa_level_id')->nullable();
            $table->string('wa_step')->nullable();             // invoice|payment|level|family|done
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
        Schema::dropIfExists('level_changes');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/LevelChange.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelChange extends Model
{
    protected $fillable = [
        'contact_id', 'member_email', 'from_type', 'to_type', 'amount_cents',
        'currency', 'family_members', 'created_family_ids',
        'stripe_customer_id', 'stripe_payment_method_id', 'stripe_payment_intent_id',
        'stripe_charge_id', 'status', 'wa_invoice_id', 'wa_bundle_id', 'wa_level_id',
        'wa_step', 'processed', 'retry_count', 'error_type', 'error_code',
        'error_decline_code', 'error_message', 'payment_method', 'card_brand',
        'card_last4', 'paid_at',
    ];

    protected $casts = [
        'family_members'     => 'array',
        'created_family_ids' => 'array',
        'processed'          => 'boolean',
        'amount_cents'       => 'integer',
        'retry_count'        => 'integer',
        'contact_id'         => 'integer',
        'wa_invoice_id'      => 'integer',
        'wa_bundle_id'       => 'integer',
        'wa_level_id'        => 'integer',
        'paid_at'            => 'datetime',
    ];
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/LevelChangeModelTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_05_16_100000_create_level_changes_table.php app/Models/LevelChange.php tests/Feature/LevelChangeModelTest.php
git commit -m "feat: add level_changes table and LevelChange model"
```

---

## Task 5: LevelChangeService — available levels

**Files:**
- Create: `app/Services/LevelChangeService.php`
- Test: `tests/Unit/LevelChangeServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/LevelChangeServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\LevelChangeService;
use App\Support\MemberProfile;
use Tests\TestCase;

class LevelChangeServiceTest extends TestCase
{
    private function profileWithLevel(string $levelName): MemberProfile
    {
        return new MemberProfile(['contact' => [
            'Id'              => 999,
            'MembershipLevel' => ['Id' => 1, 'Name' => $levelName],
            'FieldValues'     => [],
        ]]);
    }

    public function test_available_levels_excludes_current_type(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $types = array_column($levels, 'type');
        $this->assertNotContains('individual', $types, 'current type excluded');
        $this->assertCount(6, $levels);
    }

    public function test_available_levels_flag_family_and_checkomatic(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $byType = [];
        foreach ($levels as $l) {
            $byType[$l['type']] = $l;
        }

        $this->assertTrue($byType['family']['includesFamily']);
        $this->assertFalse($byType['lifetime_individual']['includesFamily']);
        $this->assertTrue($byType['checkomatic_individual']['isCheckomatic']);
        $this->assertArrayHasKey('fee', $byType['family']);
        $this->assertArrayHasKey('label', $byType['family']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/LevelChangeServiceTest.php`
Expected: FAIL — `Class "App\Services\LevelChangeService" not found`.

- [ ] **Step 3: Create the service with `availableLevels()`**

Create `app/Services/LevelChangeService.php`:

```php
<?php

namespace App\Services;

use App\Support\MemberProfile;
use App\Support\MembershipFee;
use App\Support\MembershipTypes;

/**
 * Orchestrates a membership level change: lists the available target levels,
 * resolves the target-level fee, runs the Stripe charge, and dispatches the
 * WildApricot level-change job.
 */
class LevelChangeService
{
    public function __construct(private StripeService $stripe) {}

    /**
     * The membership types the member may switch to — all 7 minus their
     * current one. Each: {type, label, fee, includesFamily, isCheckomatic}.
     * For checkomatic types the fee cents are 0 until the member enters an amount.
     */
    public function availableLevels(MemberProfile $profile): array
    {
        $currentSlug = MembershipTypes::slugFromLevelName($profile->level);

        $levels = [];
        foreach (MembershipTypes::allSlugs() as $slug) {
            if ($slug === $currentSlug) {
                continue;
            }
            $isCheckomatic = MembershipTypes::isCheckomatic($slug);
            $levels[] = [
                'type'           => $slug,
                'label'          => MembershipTypes::labelForSlug($slug),
                'fee'            => MembershipFee::resolve($slug, 0, $isCheckomatic ? null : 0.0),
                'includesFamily' => MembershipTypes::includesFamily($slug),
                'isCheckomatic'  => $isCheckomatic,
            ];
        }

        return $levels;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/LevelChangeServiceTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/LevelChangeService.php tests/Unit/LevelChangeServiceTest.php
git commit -m "feat: add LevelChangeService with available-levels listing"
```

---

## Task 6: LevelChangeService — charge() Stripe sequence

**Files:**
- Modify: `app/Services/LevelChangeService.php`
- Test: `tests/Unit/LevelChangeServiceTest.php`

- [ ] **Step 1: Add the failing tests**

Append to `tests/Unit/LevelChangeServiceTest.php`. Add these imports after the existing `use` lines:

```php
use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
```

Add `use RefreshDatabase;` as the first line inside the `LevelChangeServiceTest` class body.

Append these methods inside the class:

```php
    private function mockStripeSuccess(): void
    {
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(
            \Stripe\Customer::constructFrom(['id' => 'cus_test'])
        );
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom(['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test'])
        );
        $stripe->shouldReceive('processPayment')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test', 'status' => 'succeeded', 'latest_charge' => 'ch_test'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);
    }

    public function test_charge_success_creates_paid_level_change_and_dispatches_job(): void
    {
        Queue::fake();
        $this->mockStripeSuccess();

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'family', [], 'pm_test', null);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('level_changes', [
            'id'        => $result['level_change_id'],
            'from_type' => 'individual',
            'to_type'   => 'family',
            'status'    => 'paid',
        ]);
        Queue::assertPushed(ProcessLevelChange::class);
    }

    public function test_charge_rejects_changing_to_the_same_type(): void
    {
        Queue::fake();
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'a@b.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'individual', [], 'pm_test', null);

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('level_changes', 0);
        Queue::assertNotPushed(ProcessLevelChange::class);
    }

    public function test_charge_rejects_checkomatic_with_no_amount(): void
    {
        Queue::fake();
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'a@b.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'checkomatic_individual', [], 'pm_test', null);

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('level_changes', 0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/LevelChangeServiceTest.php`
Expected: FAIL — `Call to undefined method ...charge()`.

- [ ] **Step 3: Add `charge()` + helpers**

In `app/Services/LevelChangeService.php`, add these imports at the top:

```php
use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use Illuminate\Support\Facades\Log;
```

Add the `charge()` method and the two private helpers after `availableLevels()`:

```php
    /**
     * Run the Stripe charge for a level change. On success persists a 'paid'
     * LevelChange and dispatches ProcessLevelChange.
     *
     * @param  array  $familyMembers  Submitted spouse/family member rows (empty for non-family targets).
     * @return array{success:bool, level_change_id?:int, requires_action?:bool, client_secret?:string, payment_intent_id?:string, message?:string}
     */
    public function charge(int $contactId, MemberProfile $profile, string $toType, array $familyMembers, string $paymentMethodId, ?float $checkomaticAmount): array
    {
        $fromType = MembershipTypes::slugFromLevelName($profile->level);

        if ($toType === $fromType) {
            return ['success' => false, 'message' => 'You are already on that membership level.'];
        }
        if (! in_array($toType, MembershipTypes::allSlugs(), true)) {
            return ['success' => false, 'message' => 'Unknown membership level.'];
        }
        if (MembershipTypes::isCheckomatic($toType) && (float) ($checkomaticAmount ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Please enter your monthly contribution amount.'];
        }

        // Only family-inclusive targets keep submitted family members.
        $family = MembershipTypes::includesFamily($toType)
            ? array_values(array_filter($familyMembers, fn ($m) => ! empty($m['first_name'])))
            : [];

        $fee = MembershipFee::resolve($toType, count($family), $checkomaticAmount);

        $levelChange = LevelChange::create([
            'contact_id'               => $contactId,
            'member_email'             => $profile->email,
            'from_type'                => $fromType,
            'to_type'                  => $toType,
            'amount_cents'             => $fee['cents'],
            'currency'                 => 'usd',
            'status'                   => 'pending',
            'family_members'           => $family,
            'stripe_payment_method_id' => $paymentMethodId,
        ]);

        $description = MembershipTypes::labelForSlug($toType) . ' — Level Change — ISGH';

        try {
            $customer = $this->stripe->createCustomer([
                'name'  => trim($profile->fullName) ?: $profile->email,
                'email' => $profile->email,
                'phone' => $profile->phone ?: null,
            ]);
            $levelChange->update(['stripe_customer_id' => $customer->id]);

            $pm   = $this->stripe->addPaymentMethodToCustomer($paymentMethodId, $customer->id);
            $card = $pm->card ?? null;
            $levelChange->update([
                'payment_method' => $pm->type ?? null,
                'card_brand'     => $card->brand ?? null,
                'card_last4'     => $card->last4 ?? null,
            ]);

            $intent = $this->stripe->createPaymentIntent([
                'amount_cents' => $fee['cents'],
                'currency'     => 'usd',
                'customer_id'  => $customer->id,
                'description'  => $description,
                'metadata'     => [
                    'contact_id'      => $contactId,
                    'level_change_id' => $levelChange->id,
                    'to_type'         => $toType,
                ],
            ]);
            $levelChange->update(['stripe_payment_intent_id' => $intent->id]);

            $confirmed = $this->stripe->processPayment($intent->id, $paymentMethodId);
            $succeeded = ($confirmed->status ?? null) === 'succeeded';

            if (! $succeeded) {
                if (($confirmed->status ?? null) === 'requires_action') {
                    return [
                        'success'           => true,
                        'requires_action'   => true,
                        'client_secret'     => $confirmed->client_secret ?? '',
                        'payment_intent_id' => $intent->id,
                        'level_change_id'   => $levelChange->id,
                    ];
                }
                $levelChange->update(['status' => 'failed', 'error_message' => 'Payment status: ' . ($confirmed->status ?? 'unknown')]);
                return ['success' => false, 'message' => 'Payment could not be completed. Please try another card.'];
            }

            $levelChange->update([
                'status'           => 'paid',
                'stripe_charge_id' => $confirmed->latest_charge ?? null,
                'paid_at'          => now(),
            ]);

            ProcessLevelChange::dispatch($levelChange);

            return ['success' => true, 'level_change_id' => $levelChange->id];
        } catch (\Throwable $e) {
            $errorFields = $this->stripeErrorFields($e);
            $levelChange->update(['status' => 'failed'] + $errorFields);
            Log::error('LevelChangeService::charge failed', [
                'level_change_id' => $levelChange->id,
                'decline_code'    => $errorFields['error_decline_code'],
                'error'           => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $this->declineMessage($errorFields['error_decline_code'])];
        }
    }

    /**
     * @return array{error_type:string,error_code:?string,error_decline_code:?string,error_message:string}
     */
    private function stripeErrorFields(\Throwable $e): array
    {
        if (! ($e instanceof \Stripe\Exception\ApiErrorException)) {
            return [
                'error_type'         => 'api_error',
                'error_code'         => null,
                'error_decline_code' => null,
                'error_message'      => $e->getMessage(),
            ];
        }
        $err = $e->getError();
        return [
            'error_type'         => $err->type ?? 'api_error',
            'error_code'         => $err->code ?? null,
            'error_decline_code' => $err->decline_code ?? null,
            'error_message'      => $e->getMessage(),
        ];
    }

    private function declineMessage(?string $declineCode): string
    {
        return match ($declineCode) {
            'insufficient_funds'       => 'Your card has insufficient funds.',
            'card_declined'            => 'Your card was declined. Please try a different card.',
            'expired_card'             => 'Your card has expired.',
            'incorrect_cvc'            => 'The card security code is incorrect.',
            'lost_card', 'stolen_card' => 'This card cannot be used. Please contact your bank.',
            default                    => 'Payment failed. Please try a different card.',
        };
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/LevelChangeServiceTest.php`
Expected: PASS — 5 tests (the `charge()` tests can't fully pass until `ProcessLevelChange` exists for the `dispatch()`/`Queue::assertPushed`. `ProcessLevelChange` is Task 7. If the `dispatch()` line errors with "class not found", create a minimal stub now is NOT needed — instead, reorder: do Task 7 before Step 4 here. To keep tasks independent: this Step 4 EXPECTS `App\Jobs\ProcessLevelChange` to exist. If it does not yet, this task is blocked on Task 7. Therefore: implement Task 7 FIRST, then return to run this Step 4.) — Once `ProcessLevelChange` exists, expect PASS, 5 tests.

NOTE TO IMPLEMENTER: Tasks 6 and 7 are mutually referential (`charge()` dispatches the job; the job is a separate class). Implement Task 7's `ProcessLevelChange` class file FIRST (it has no dependency on `charge()`), then complete Task 6 Step 4. The controller wiring (Task 8) depends on both. If executing strictly in order, treat Task 6 Step 3 as "write the code" and Task 6 Step 4 as deferred until Task 7 Step 3 is done.

- [ ] **Step 5: Commit**

```bash
git add app/Services/LevelChangeService.php tests/Unit/LevelChangeServiceTest.php
git commit -m "feat: add Stripe charge sequence to LevelChangeService"
```

---

## Task 7: ProcessLevelChange job

**Files:**
- Create: `app/Jobs/ProcessLevelChange.php`
- Test: `tests/Feature/ProcessLevelChangeTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ProcessLevelChangeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use App\Services\WildApricotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessLevelChangeTest extends TestCase
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
            'api.wildapricot.org/v2.3/accounts/12345/invoices' => Http::response(['Id' => 555], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments' => Http::response(['Id' => 777], 200),
            'api.wildapricot.org/v2.3/accounts/12345/paymentsystemtenders*' => Http::response([
                ['Id' => 3, 'Name' => 'Stripe'],
            ], 200),
            // level switch to 'family' resolves the level id from membershiplevels.
            'api.wildapricot.org/v2.3/accounts/12345/membershiplevels' => Http::response([
                ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)', 'MembershipFee' => 40.0],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            // primary contact GET (updateMember reads current) + PUT response carries BundleId.
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                'FieldValues' => [
                    ['FieldName' => 'Bundle ID', 'SystemCode' => 'BundleId', 'Value' => 4242],
                ],
            ], 200),
            // related-contact creation (spouse).
            'api.wildapricot.org/v2.3/accounts/12345/contacts' => Http::response(['Id' => 1001], 200),
        ]);
    }

    public function test_job_creates_invoice_records_payment_switches_level_marks_processed(): void
    {
        $this->fakeWa();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'paid',
            'stripe_charge_id' => 'ch_test', 'family_members' => [],
        ]);

        (new ProcessLevelChange($lc))->handle(app(WildApricotService::class));

        $lc->refresh();
        $this->assertTrue($lc->processed);
        $this->assertSame('done', $lc->wa_step);
        $this->assertSame(555, $lc->wa_invoice_id);
        $this->assertSame('processed', $lc->status);
    }

    public function test_job_creates_family_members(): void
    {
        $this->fakeWa();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'paid',
            'stripe_charge_id' => 'ch_test',
            'family_members' => [
                ['first_name' => 'Sarah', 'last_name' => 'Alam', 'email' => 'sarah@example.com'],
            ],
        ]);

        (new ProcessLevelChange($lc))->handle(app(WildApricotService::class));

        $lc->refresh();
        $this->assertTrue($lc->processed);
        $this->assertSame([1001], $lc->created_family_ids);

        // A POST to /contacts (related-contact creation) must have been sent.
        Http::assertSent(fn ($request) =>
            $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/accounts/12345/contacts')
        );
    }

    public function test_job_is_idempotent_when_already_processed(): void
    {
        $this->fakeWa();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'processed',
            'processed' => true, 'wa_step' => 'done', 'family_members' => [],
        ]);

        (new ProcessLevelChange($lc))->handle(app(WildApricotService::class));

        $this->assertTrue($lc->fresh()->processed);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/ProcessLevelChangeTest.php`
Expected: FAIL — `Class "App\Jobs\ProcessLevelChange" not found`.

- [ ] **Step 3: Create the job**

Create `app/Jobs/ProcessLevelChange.php`:

```php
<?php

namespace App\Jobs;

use App\Models\LevelChange;
use App\Services\MemberPortalService;
use App\Services\WildApricotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes the WildApricot side of a membership level change after Stripe has
 * confirmed payment: creates the invoice, records the payment, switches the
 * membership level, and creates each newly-submitted family member as a
 * related WA contact.
 *
 * Idempotent — re-running resumes from the first incomplete step; family
 * members already created (tracked by index in created_family_ids) are skipped.
 */
class ProcessLevelChange implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public LevelChange $levelChange) {}

    public function handle(WildApricotService $wa): void
    {
        $levelChange = $this->levelChange->fresh();

        if ($levelChange->processed) {
            Log::info('ProcessLevelChange: already processed, skipping', ['level_change_id' => $levelChange->id]);
            return;
        }

        $contactId = (int) $levelChange->contact_id;
        $toType    = (string) $levelChange->to_type;
        $amount    = $levelChange->amount_cents / 100;

        $fail = function (string $step, Throwable $e) use ($levelChange): never {
            $levelChange->update([
                'wa_step'       => $step,
                'error_message' => $e->getMessage(),
                'retry_count'   => $levelChange->retry_count + 1,
            ]);
            Log::error("ProcessLevelChange failed at step [{$step}]", [
                'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
            ]);
            throw $e;
        };

        // ── Step 1: Invoice ───────────────────────────────────────────────
        $invoiceId = (int) ($levelChange->wa_invoice_id ?? 0);
        if (! $invoiceId) {
            $levelChange->update(['wa_step' => 'invoice']);
            try {
                $invoice   = $wa->createMembershipInvoice($contactId, $amount, $toType);
                $invoiceId = (int) $invoice['Id'];
                $levelChange->update(['wa_invoice_id' => $invoiceId]);
                try {
                    $wa->setInvoiceNumberOnContact($contactId, $invoiceId);
                } catch (Throwable $e) {
                    Log::warning('ProcessLevelChange: Invoice# field update failed', [
                        'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('invoice', $e);
            }
        }

        // ── Step 2: Record payment ────────────────────────────────────────
        if (! in_array($levelChange->wa_step, ['payment', 'level', 'family', 'done'], true)) {
            $levelChange->update(['wa_step' => 'payment']);
            try {
                $wa->recordPayment(
                    $contactId,
                    $invoiceId,
                    $amount,
                    (string) ($levelChange->stripe_charge_id ?? ''),
                    (string) ($levelChange->stripe_payment_method_id ?? '')
                );
                try {
                    $wa->setPaymentProcessedOnContact($contactId, (string) ($levelChange->stripe_charge_id ?? ''));
                } catch (Throwable $e) {
                    Log::warning('ProcessLevelChange: Payment Processed field update failed', [
                        'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                $fail('payment', $e);
            }
        }

        // ── Step 3: Switch the membership level ───────────────────────────
        $levelChange->update(['wa_step' => 'level']);
        try {
            $updated  = $wa->updateMember($contactId, ['membership_type' => $toType]);
            $bundleId = (int) $wa->extractFieldValue($updated, 'BundleId');
            $levelId  = (int) ($updated['MembershipLevel']['Id'] ?? 0);
            // Fall back to a fresh fetch if the PUT response lacked the fields.
            if ($bundleId === 0 || $levelId === 0) {
                $fresh    = $wa->getContactById($contactId) ?? [];
                $bundleId = $bundleId ?: (int) $wa->extractFieldValue($fresh, 'BundleId');
                $levelId  = $levelId ?: (int) ($fresh['MembershipLevel']['Id'] ?? 0);
            }
            $levelChange->update(['wa_bundle_id' => $bundleId, 'wa_level_id' => $levelId]);
        } catch (Throwable $e) {
            $fail('level', $e);
        }

        // ── Step 4: Create newly-added family members ─────────────────────
        $levelChange->update(['wa_step' => 'family']);
        try {
            $family       = $levelChange->family_members ?? [];
            $createdIds   = $levelChange->created_family_ids ?? [];
            $createdIndex = array_keys($createdIds); // not used; track by position below
            $bundleId     = (int) $levelChange->wa_bundle_id;
            $levelId      = (int) $levelChange->wa_level_id;

            foreach ($family as $idx => $member) {
                if (empty($member['first_name'])) {
                    continue;
                }
                // Skip a member already created on a previous run.
                if (array_key_exists((string) $idx, $createdIds) || in_array($idx, array_keys($createdIds), true)) {
                    continue;
                }
                try {
                    $related = $wa->addRelatedContact($contactId, $bundleId, $levelId, array_merge($member, [
                        'membership_type' => $toType,
                    ]));
                    $createdIds[$idx] = (int) ($related['Id'] ?? 0);
                    $levelChange->update(['created_family_ids' => $createdIds]);
                } catch (Throwable $e) {
                    // Non-fatal — one bad family record must not fail the level change.
                    Log::warning('ProcessLevelChange: family member create failed', [
                        'level_change_id' => $levelChange->id, 'index' => $idx, 'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            $fail('family', $e);
        }

        // ── Done ──────────────────────────────────────────────────────────
        $levelChange->update(['wa_step' => 'done', 'processed' => true, 'status' => 'processed']);

        try {
            app(MemberPortalService::class)->invalidate($contactId);
        } catch (Throwable $e) {
            Log::warning('ProcessLevelChange: cache invalidation failed', [
                'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
            ]);
        }
    }

    /** Called when all retry attempts are exhausted. */
    public function failed(Throwable $e): void
    {
        $this->levelChange->fresh()?->update([
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
        ]);
        Log::error('ProcessLevelChange: permanently failed after retries', [
            'level_change_id' => $this->levelChange->id, 'error' => $e->getMessage(),
        ]);
    }
}
```

NOTE: The `created_family_ids` skip uses the member's array index as the key. The check `array_key_exists((string) $idx, $createdIds)` covers JSON-decoded string keys; `in_array($idx, array_keys($createdIds), true)` covers integer keys. This makes a retry skip an already-created member. (`$createdIndex` is leftover — remove that unused line; the two-part check is what matters.)

Clean version of the skip — use exactly this in the loop instead of the `$createdIndex` line + the `if`:
```php
            foreach ($family as $idx => $member) {
                if (empty($member['first_name'])) {
                    continue;
                }
                if (array_key_exists($idx, $createdIds) || array_key_exists((string) $idx, $createdIds)) {
                    continue; // already created on a prior run
                }
```
And delete the `$createdIndex = array_keys($createdIds);` line.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/ProcessLevelChangeTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/ProcessLevelChange.php tests/Feature/ProcessLevelChangeTest.php
git commit -m "feat: add ProcessLevelChange job for WildApricot level-change side"
```

- [ ] **Step 6: Complete Task 6 Step 4**

Now that `ProcessLevelChange` exists, run: `php artisan test tests/Unit/LevelChangeServiceTest.php`
Expected: PASS — 5 tests. If `charge()` was committed in Task 6 Step 5 already, no commit needed; otherwise commit per Task 6 Step 5.

---

## Task 8: LevelChangeService — finalize() for 3DS

**Files:**
- Modify: `app/Services/LevelChangeService.php`
- Test: `tests/Unit/LevelChangeServiceTest.php`

- [ ] **Step 1: Add the failing test**

Append to `tests/Unit/LevelChangeServiceTest.php` inside the class:

```php
    public function test_finalize_marks_paid_and_dispatches_job(): void
    {
        Queue::fake();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'pending',
            'stripe_payment_intent_id' => 'pi_3ds', 'family_members' => [],
        ]);

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('getPaymentIntent')->with('pi_3ds')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_3ds', 'status' => 'succeeded', 'latest_charge' => 'ch_3ds'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $result = app(LevelChangeService::class)->finalize($lc->id, 'pi_3ds');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('level_changes', ['id' => $lc->id, 'status' => 'paid']);
        Queue::assertPushed(ProcessLevelChange::class);
    }

    public function test_finalize_is_idempotent_for_already_paid(): void
    {
        Queue::fake();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'paid',
            'stripe_payment_intent_id' => 'pi_3ds', 'family_members' => [],
        ]);

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $result = app(LevelChangeService::class)->finalize($lc->id, 'pi_3ds');

        $this->assertTrue($result['success']);
        Queue::assertNotPushed(ProcessLevelChange::class);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/LevelChangeServiceTest.php`
Expected: FAIL — `Call to undefined method ...finalize()`.

- [ ] **Step 3: Add `finalize()`**

In `app/Services/LevelChangeService.php`, add this method after `charge()`:

```php
    /**
     * Finalize a level change whose payment required 3DS authentication.
     * Re-checks the PaymentIntent and, if it succeeded, marks the EXISTING
     * LevelChange paid and dispatches the job — no new charge.
     *
     * @return array{success:bool, level_change_id?:int, message?:string}
     */
    public function finalize(int $levelChangeId, string $paymentIntentId): array
    {
        $levelChange = LevelChange::find($levelChangeId);
        if (! $levelChange) {
            return ['success' => false, 'message' => 'Level change not found.'];
        }

        if ($levelChange->processed || $levelChange->status === 'paid') {
            return ['success' => true, 'level_change_id' => $levelChange->id];
        }

        try {
            $intent = $this->stripe->getPaymentIntent($paymentIntentId);
        } catch (\Throwable $e) {
            $levelChange->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('LevelChangeService::finalize intent retrieval failed', [
                'level_change_id' => $levelChange->id, 'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Could not verify payment. Please try again.'];
        }

        if (($intent->status ?? null) !== 'succeeded') {
            return [
                'success' => false,
                'message' => 'Payment is not complete. Status: ' . ($intent->status ?? 'unknown'),
            ];
        }

        $levelChange->update([
            'status'           => 'paid',
            'stripe_charge_id' => $intent->latest_charge ?? null,
            'paid_at'          => now(),
        ]);

        ProcessLevelChange::dispatch($levelChange);

        return ['success' => true, 'level_change_id' => $levelChange->id];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/LevelChangeServiceTest.php`
Expected: PASS — 7 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/LevelChangeService.php tests/Unit/LevelChangeServiceTest.php
git commit -m "feat: add 3DS finalize to LevelChangeService"
```

---

## Task 9: Level-change routes + controller endpoints

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/MemberPortalController.php`
- Test: `tests/Feature/MembershipLevelChangeTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MembershipLevelChangeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class MembershipLevelChangeTest extends TestCase
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
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
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

    public function test_change_level_options_excludes_current_type(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson('/member-portal/change-level/options')
          ->assertOk()
          ->assertJsonPath('success', true);

        $res = $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson('/member-portal/change-level/options')->json();

        $types = array_column($res['levels'], 'type');
        $this->assertNotContains('individual', $types);
        $this->assertCount(6, $types);
    }

    public function test_process_level_change_charges_and_creates_row(): void
    {
        Queue::fake();

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(\Stripe\Customer::constructFrom(['id' => 'cus_t']));
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom(['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(\Stripe\PaymentIntent::constructFrom(['id' => 'pi_t']));
        $stripe->shouldReceive('processPayment')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_t', 'status' => 'succeeded', 'latest_charge' => 'ch_t'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->postJson('/member-portal/change-level', [
            'target_type'       => 'family',
            'payment_method_id' => 'pm_t',
            'family_members'    => [['first_name' => 'Sarah', 'last_name' => 'Alam']],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('level_changes', ['contact_id' => 999, 'to_type' => 'family', 'status' => 'paid']);
        Queue::assertPushed(ProcessLevelChange::class);
    }

    public function test_process_level_change_rejects_same_type(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->postJson('/member-portal/change-level', [
            'target_type'       => 'individual',
            'payment_method_id' => 'pm_t',
        ])->assertStatus(402);
    }

    public function test_level_change_status_reflects_state(): void
    {
        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'processed',
            'processed' => true, 'wa_invoice_id' => 555, 'family_members' => [],
        ]);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson("/member-portal/change-level/status/{$lc->id}")
          ->assertOk()
          ->assertJson(['processed' => true, 'wa_invoice_id' => 555]);
    }

    public function test_level_change_status_rejects_another_member(): void
    {
        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'processed',
            'processed' => true, 'family_members' => [],
        ]);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->getJson("/member-portal/change-level/status/{$lc->id}")
          ->assertStatus(404);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/MembershipLevelChangeTest.php`
Expected: FAIL — 404, the routes don't exist.

- [ ] **Step 3: Add the routes**

In `routes/web.php`, inside the `member-portal` group's `member.portal.auth` middleware block (where the `renew*` routes are), add:

```php
        Route::get('/change-level/options',          [MemberPortalController::class, 'changeLevelOptions'])->name('change-level.options');
        Route::post('/change-level',                 [MemberPortalController::class, 'processLevelChange'])->name('change-level');
        Route::post('/change-level/finalize',        [MemberPortalController::class, 'finalizeLevelChange'])->name('change-level.finalize');
        Route::get('/change-level/status/{levelChange}', [MemberPortalController::class, 'levelChangeStatus'])->name('change-level.status');
```

- [ ] **Step 4: Add the controller endpoints**

In `app/Http/Controllers/MemberPortalController.php`, add these imports at the top (after the existing `use` lines):

```php
use App\Models\LevelChange;
use App\Services\LevelChangeService;
```

(`MemberPortalService`, `MemberProfile`, `Log` are already imported — verify; add only what's missing.)

Add these four methods after the existing `renewStatus()` method (and before `logout()`):

```php
    // ── Membership Level Change ───────────────────────────────────────────

    /** The membership levels the member may switch to. */
    public function changeLevelOptions(Request $request, MemberPortalService $portal, LevelChangeService $levelChange)
    {
        $contactId = $request->session()->get('member_portal_contact_id');
        if (! $contactId) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $profile = new MemberProfile($portal->getBundle((int) $contactId));

        return response()->json([
            'success' => true,
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
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/MembershipLevelChangeTest.php`
Expected: PASS — 6 tests.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/MemberPortalController.php tests/Feature/MembershipLevelChangeTest.php
git commit -m "feat: add level-change endpoints to MemberPortalController"
```

---

## Task 10: Wire the dashboard Change Level modal

**Files:**
- Modify: `resources/views/member-portal/dashboard.blade.php`

- [ ] **Step 1: Read the current dashboard**

Read `resources/views/member-portal/dashboard.blade.php` IN FULL. Locate:
- The "Change Level" item in the Quick Links card (an `<a>` with the text "Change Level").
- The existing renewal modal markup + its renewal `<script>` IIFE (added in the renewal feature) — the level-change modal mirrors its structure.
- The `<head>` — confirm `<meta name="csrf-token">` and `<script src="https://js.stripe.com/v3/">` exist (both added by the renewal feature).

- [ ] **Step 2: Tag the Change Level link**

Add a class `ql-change-level` to the Quick Links "Change Level" `<a>` so the JS can target it, and `preventDefault` it (it's an `<a href="#">`).

- [ ] **Step 3: Add the level-change modal markup**

Before `</body>`, add a modal `id="levelModal"` reusing the renewal modal's overlay/card CSS classes (the `.renew-*` classes from the renewal feature — reuse them, or add parallel `.lvl-*` classes copying the same rules). Four steps:
- `#lvlScreenPick` — heading "Change Membership Level"; a container `#lvlOptions` the JS fills with one selectable card per available level (each card shows the label + fee, has `data-type`, `data-includes-family`, `data-checkomatic`); a "Continue" button `#lvlPickNext` (disabled until a card is selected).
- `#lvlScreenFamily` — heading "Add Family Members"; a container `#lvlFamilyContainer` with dynamic add/remove member blocks (each block: first name, last name, email, phone, dob inputs); an "Add Member" button `#lvlAddMember`; "Back" and "Continue" buttons.
- `#lvlScreenPay` — a "Pending Payment" amount `#lvlAmountLabel`; checkomatic monthly input wrapped in `#lvlMonthlyWrap` (hidden) with `#lvlMonthlyInput`; Stripe card mount `#lvl-card-element`; error `#lvlPayError`; "Back" and "Pay" `#lvlPayBtn`.
- `#lvlScreenSuccess` — heading "Level Changed!"; line `#lvlSuccessLabel`; "Go to Dashboard" button.

- [ ] **Step 4: Add the level-change JavaScript**

In the dashboard `<script>` area, add a self-contained IIFE (separate from the renewal IIFE). It must:
1. `const lvlCsrf = document.querySelector('meta[name="csrf-token"]').content;` and the Stripe key `'{{ config("services.stripe.key") }}'`.
2. Open `#levelModal` on `.ql-change-level` click; `fetch('{{ route('member-portal.change-level.options') }}')`; render one card into `#lvlOptions` per `levels[]` entry (label, `fee.label`, the data-attrs).
3. Card click → mark selected, enable `#lvlPickNext`.
4. `#lvlPickNext` → if the selected card's `data-includes-family` is true, show `#lvlScreenFamily`; else show `#lvlScreenPay`. The family screen's "Continue" goes to `#lvlScreenPay`.
5. `#lvlAddMember` clones a member block into `#lvlFamilyContainer`; each block has a remove button.
6. Entering `#lvlScreenPay`: set `#lvlAmountLabel` to the selected fee label (for checkomatic show "Enter your monthly amount below" and reveal `#lvlMonthlyWrap`); lazily init Stripe + mount the card element into `#lvl-card-element` (guard so it mounts once).
7. `#lvlPayBtn` → disable it (in-flight guard, re-enable in `finally`); if checkomatic, read+validate `#lvlMonthlyInput` (> 0); `stripe.createPaymentMethod({type:'card', card: cardElement})`; collect the family-member blocks into an array; `POST {{ route('member-portal.change-level') }}` with JSON `{target_type, payment_method_id, monthly_amount, family_members}` + `X-CSRF-TOKEN`.
8. If `requires_action`: `stripe.confirmCardPayment(client_secret)`, then `POST {{ route('member-portal.change-level.finalize') }}` with `{level_change_id, payment_intent_id}` — do NOT re-POST `/change-level`.
9. On success: poll `'{{ url('/member-portal/change-level/status') }}/' + id` (~1.5s × 8) until `processed === true` → show `#lvlScreenSuccess` with `#lvlSuccessLabel` = "You are now on the X plan. Invoice INV-…". If a poll returns `status === 'failed'`, stop and show a "payment received, contact support" message. On timeout, show a "finalizing…" fallback.
10. On `{success:false}`: show `data.message` in `#lvlPayError`, re-enable the Pay button.

IMPORTANT: Mirror the renewal feature's modal JS exactly for the Stripe init / `createPaymentMethod` / `confirmCardPayment` / status-poll patterns — read the renewal IIFE in the same file and copy its structure. Only the endpoints, the extra "pick level" + "family" steps, and the element IDs differ.

- [ ] **Step 5: Verify the view compiles**

Run: `php artisan view:cache 2>&1` — confirm no Blade error mentioning `dashboard.blade.php`. Then `php artisan view:clear`.
Run: `php artisan test tests/Feature/MembershipLevelChangeTest.php` — confirm the 6 controller tests still pass.

- [ ] **Step 6: Commit**

```bash
git add resources/views/member-portal/dashboard.blade.php
git commit -m "feat: wire dashboard Change Level modal to level-change endpoints"
```

---

## Task 11: Manual end-to-end smoke test

**Files:** none (verification only)

- [ ] **Step 1: Change level on a non-family → family plan**

With the app running and Stripe in test mode, log into the portal as an Individual member. Quick Links → "Change Level" → pick "Family Membership" → add a spouse → pay with test card `4242 4242 4242 4242`. Confirm the success screen shows "Level Changed!" with an invoice number.

- [ ] **Step 2: Verify WildApricot**

In WildApricot, confirm the member's level switched to Family, a renewal invoice was created, the payment recorded, and the added spouse exists as a related contact linked to the primary's bundle.

- [ ] **Step 3: Verify a non-family target**

Change a member to "Individual" (from a different plan) — confirm no family step appears, the level switches, invoice + payment recorded.

- [ ] **Step 4: Verify checkomatic**

Change to a Checkomatic plan — confirm the monthly-amount input appears on the payment screen and the charge matches the entered amount.

- [ ] **Step 5: Reconcile if needed**

If a real WA `MembershipLevel.Name` doesn't map correctly, fix `MembershipTypes::LEVEL_NAME_TO_SLUG`, re-run `php artisan test tests/Unit/MembershipTypesTest.php`, and commit:

```bash
git add app/Support/MembershipTypes.php
git commit -m "fix: correct WA membership-level name mapping after live verification"
```

---

## Self-Review Notes

- **Spec coverage:** shared `MembershipTypes` + `MembershipFee` (T1-T2); `RenewalService` delegation refactor, outputs unchanged (T3); `level_changes` table + model with `family_members`/`created_family_ids` JSON (T4); `LevelChangeService` — `availableLevels` excluding current type + family/checkomatic flags (T5), `charge()` Stripe sequence with same-type + checkomatic-no-amount guards + decline capture (T6), `finalize()` idempotent 3DS (T8); `ProcessLevelChange` job — invoice/payment/level-switch/family-creation, BundleId capture, idempotency incl. per-member skip via `created_family_ids`, `failed()` hook (T7); controller endpoints `changeLevelOptions`/`processLevelChange`/`finalizeLevelChange`/`levelChangeStatus` with session + ownership + same-type guards (T9); dashboard multi-step modal — pick level → conditional family step → payment → success, Stripe + 3DS (T10); manual smoke incl. family upgrade + non-family + checkomatic (T11). All spec sections covered.
- **Type consistency:** `MembershipTypes` static methods (`slugFromLevelName`, `labelForSlug`, `isLifetime`, `isCheckomatic`, `includesFamily`, `allSlugs`) and `MembershipFee::resolve()` consistent across T1/T2 and their callers T3/T5/T6. `LevelChangeService` methods (`availableLevels`, `resolveFee` via `MembershipFee`, `charge`, `finalize`) consistent across T5/T6/T8 and the controller T9. `LevelChange` model fields match the migration columns (T4) and every write in `charge()` (T6), `finalize()` (T8), and the job (T7). `ProcessLevelChange(LevelChange $levelChange)` constructor consistent across T7 and the `dispatch()` calls in T6/T8. `charge()`/`finalize()` return shapes (`success`/`level_change_id`/`requires_action`/`client_secret`/`message`) match what `processLevelChange`/`finalizeLevelChange` return (T9) and what the JS consumes (T10).
- **No placeholders:** every code step has complete code; T10's Blade/JS steps direct the implementer to mirror the existing renewal modal IIFE with concrete element IDs and routes. The Task 6/7 mutual reference is explicitly sequenced (implement T7's job class before T6's Step 4).

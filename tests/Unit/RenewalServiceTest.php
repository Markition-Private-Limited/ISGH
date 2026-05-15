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

    public function test_is_lifetime_level_true_for_lifetime_false_otherwise(): void
    {
        $svc = app(RenewalService::class);

        $this->assertTrue($svc->isLifetimeLevel($this->profileWithLevel('Lifetime')));
        $this->assertFalse($svc->isLifetimeLevel($this->profileWithLevel('Individual')));
    }

    public function test_fallback_match_resolves_unmapped_checkomatic_family_name(): void
    {
        $svc = app(RenewalService::class);

        // A level name not in LEVEL_NAME_TO_SLUG must fall through to the
        // substring match; "checkomatic" is checked before "family".
        $this->assertSame('checkomatic_family', $svc->resolveTypeSlug(
            $this->profileWithLevel('Checkomatic Family Plan 2026')
        ));
    }

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
}

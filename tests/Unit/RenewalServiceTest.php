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
}

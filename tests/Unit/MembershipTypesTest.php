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

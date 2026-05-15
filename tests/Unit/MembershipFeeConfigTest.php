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

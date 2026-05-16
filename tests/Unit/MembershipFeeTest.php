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

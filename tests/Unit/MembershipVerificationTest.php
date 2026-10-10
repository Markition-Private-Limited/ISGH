<?php

namespace Tests\Unit;

use App\Support\MembershipVerification as MV;
use Tests\TestCase;

class MembershipVerificationTest extends TestCase
{
    public function test_active_registered_on_cutoff_is_eligible(): void
    {
        $this->assertSame(MV::ACTIVE_ELIGIBLE, MV::scenario('Active', '2026-06-30'));
        $this->assertSame(MV::ACTIVE_ELIGIBLE, MV::scenario('active', '2020-01-15T00:00:00-06:00'));
    }

    public function test_active_registered_after_cutoff_is_not_eligible(): void
    {
        $this->assertSame(MV::ACTIVE_LATE, MV::scenario('Active', '2026-07-01'));
        $this->assertSame(MV::ACTIVE_LATE, MV::scenario('Active', '2026-09-10T08:00:00-05:00'));
    }

    public function test_missing_registration_date_does_not_block_active_member(): void
    {
        $this->assertSame(MV::ACTIVE_ELIGIBLE, MV::scenario('Active', null));
        $this->assertSame(MV::ACTIVE_ELIGIBLE, MV::scenario('Active', 'garbage'));
    }

    public function test_lapsed_and_other_statuses(): void
    {
        $this->assertSame(MV::LAPSED, MV::scenario('Lapsed', '2020-01-01'));
        $this->assertSame(MV::OTHER, MV::scenario('PendingNew', '2020-01-01'));
    }

    public function test_blank_values_display_not_on_file(): void
    {
        $this->assertSame('Not on file', MV::display(''));
        $this->assertSame('Not on file', MV::display(null));
        $this->assertSame('Not on file', MV::display('—'));
        $this->assertSame('Not on file', MV::formatDate(''));
        $this->assertSame('06-30-2026', MV::formatDate('2026-06-30'));
    }
}

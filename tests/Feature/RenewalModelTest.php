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

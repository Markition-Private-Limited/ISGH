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

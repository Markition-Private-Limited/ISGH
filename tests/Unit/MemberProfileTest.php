<?php

namespace Tests\Unit;

use App\Support\MemberProfile;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    private function bundle(array $overrides = []): array
    {
        $base = require base_path('tests/Fixtures/wa_contact.php');
        return array_merge($base, $overrides);
    }

    public function test_maps_basic_contact_fields(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertSame('Tauqeer', $p->firstName);
        $this->assertSame('Alam', $p->lastName);
        $this->assertSame('Tauqeer Alam', $p->fullName);
        $this->assertSame('tauqeer@example.com', $p->email);
        $this->assertSame('2154389281', $p->phone);
        $this->assertSame('Active', $p->status);
        $this->assertSame('Individual Membership', $p->level);
    }

    public function test_maps_custom_field_values(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertSame('7829 Southwest Freeway', $p->street);
        $this->assertSame('Texas City', $p->city);
        $this->assertSame('Texas', $p->state);
        $this->assertSame('78933', $p->zip);
        $this->assertSame('2005-11-09', $p->dob);
        $this->assertSame('2427896483965', $p->txId);
    }

    public function test_unwraps_choice_field_to_label(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('Spring Branch Islamic Center', $p->zone);
    }

    public function test_missing_fields_return_empty_string(): void
    {
        $p = new MemberProfile(['contact' => ['Id' => 1, 'FieldValues' => []]]);

        $this->assertSame('', $p->firstName);
        $this->assertSame('', $p->street);
        $this->assertSame('', $p->zone);
    }

    public function test_handles_non_array_field_values_safely(): void
    {
        $p = new MemberProfile(['contact' => ['Id' => 1, 'FieldValues' => 'broken']]);
        $this->assertSame('', $p->city);
    }
}

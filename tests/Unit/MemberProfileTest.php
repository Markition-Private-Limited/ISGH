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

    public function test_is_expired_true_for_expired_statuses(): void
    {
        foreach (['Expired', 'Lapsed', 'Overdue'] as $status) {
            $b = $this->bundle();
            $b['contact']['Status'] = $status;
            $this->assertTrue((new MemberProfile($b))->isExpired(), "$status should be expired");
        }
    }

    public function test_is_expired_false_for_active(): void
    {
        $this->assertFalse((new MemberProfile($this->bundle()))->isExpired());
    }

    public function test_renewal_formatted_returns_human_date(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('January 15, 2027', $p->renewalFormatted());
    }

    public function test_member_since_formatted_returns_human_date(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('August 22, 2021', $p->memberSinceFormatted());
    }

    public function test_dob_formatted_returns_human_date(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('November 09, 2005', $p->dobFormatted());
    }

    public function test_formatted_helpers_return_empty_for_blank(): void
    {
        $p = new MemberProfile(['contact' => ['Id' => 1, 'FieldValues' => []]]);
        $this->assertSame('', $p->renewalFormatted());
        $this->assertSame('', $p->dobFormatted());
    }

    public function test_days_left_positive_for_future_renewal(): void
    {
        $b = $this->bundle();
        $b['contact']['FieldValues'][] = ['SystemCode' => 'RenewalDue', 'FieldName' => 'Renewal due', 'Value' => now()->addDays(30)->toIso8601String()];
        $p = new MemberProfile($b);
        $this->assertGreaterThan(0, $p->daysLeft());
        $this->assertNull($p->daysOverdue());
    }

    public function test_days_overdue_positive_for_past_renewal(): void
    {
        $b = $this->bundle();
        // Replace RenewalDue with a past date.
        foreach ($b['contact']['FieldValues'] as &$fv) {
            if (($fv['SystemCode'] ?? '') === 'RenewalDue') {
                $fv['Value'] = now()->subDays(12)->toIso8601String();
            }
        }
        unset($fv);
        $p = new MemberProfile($b);
        $this->assertGreaterThan(0, $p->daysOverdue());
        $this->assertNull($p->daysLeft());
    }
}

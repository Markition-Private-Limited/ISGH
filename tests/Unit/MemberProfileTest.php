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

    public function test_member_since_and_renewal_read_from_top_level_contact_keys(): void
    {
        // WildApricot returns MemberSince / RenewalDue as top-level contact
        // properties, not inside FieldValues. The DTO must read them there.
        $p = new MemberProfile(['contact' => [
            'Id'          => 1,
            'MemberSince' => '2021-08-22T00:00:00',
            'RenewalDue'  => '2027-01-15T00:00:00',
            'FieldValues' => [],
        ]]);

        $this->assertSame('August 22, 2021', $p->memberSinceFormatted());
        $this->assertSame('January 15, 2027', $p->renewalFormatted());
    }

    public function test_top_level_key_takes_precedence_over_field_value(): void
    {
        // When both exist, the top-level contact key wins.
        $p = new MemberProfile(['contact' => [
            'Id'          => 1,
            'MemberSince' => '2020-01-01T00:00:00',
            'FieldValues' => [
                ['SystemCode' => 'MemberSince', 'FieldName' => 'Member since', 'Value' => '1999-12-31T00:00:00'],
            ],
        ]]);

        $this->assertSame('January 01, 2020', $p->memberSinceFormatted());
    }

    public function test_yearly_fee_from_resolved_membership_level_fee(): void
    {
        // assembleBundle resolves the level fee into bundle['membershipFee'].
        $p = new MemberProfile($this->bundle());
        $this->assertSame('$200.00', $p->yearlyFee);
    }

    public function test_yearly_fee_falls_back_to_field_value(): void
    {
        $p = new MemberProfile(['contact' => [
            'Id'          => 1,
            'FieldValues' => [
                ['SystemCode' => 'MembershipFee', 'FieldName' => 'Membership fee', 'Value' => '150'],
            ],
        ]]);
        $this->assertSame('$150.00', $p->yearlyFee);
    }

    public function test_yearly_fee_blank_when_unknown(): void
    {
        $p = new MemberProfile(['contact' => ['Id' => 1, 'FieldValues' => []]]);
        $this->assertSame('', $p->yearlyFee);
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
        // Replace RenewalDue in place — field() returns the FIRST match, so appending would be ignored.
        foreach ($b['contact']['FieldValues'] as &$fv) {
            if (($fv['SystemCode'] ?? '') === 'RenewalDue') {
                $fv['Value'] = now()->addDays(30)->toIso8601String();
            }
        }
        unset($fv);
        $p = new MemberProfile($b);
        $this->assertSame(30, $p->daysLeft());
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

    public function test_family_members_are_wrapped_as_member_profiles(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertCount(1, $p->family);
        $this->assertInstanceOf(MemberProfile::class, $p->family[0]);
        $this->assertSame('Sarah Alam', $p->family[0]->fullName);
    }

    public function test_spouse_is_first_family_member(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertTrue($p->hasSpouse());
        $this->assertInstanceOf(MemberProfile::class, $p->spouse());
        $this->assertSame('Sarah Alam', $p->spouse()->fullName);
    }

    public function test_no_spouse_when_family_empty(): void
    {
        $b = $this->bundle();
        $b['family'] = [];
        $p = new MemberProfile($b);

        $this->assertFalse($p->hasSpouse());
        $this->assertNull($p->spouse());
        $this->assertFalse($p->hasFamily());
    }

    public function test_invoices_are_normalized(): void
    {
        $p = new MemberProfile($this->bundle());

        $this->assertCount(2, $p->invoices);
        $this->assertTrue($p->hasInvoices());
        $inv = $p->invoices[0];
        $this->assertSame('INV-2026-0001', $inv['number']);
        $this->assertSame('2026-01-15', $inv['date']);
        $this->assertSame('January 15, 2026', $inv['dateLabel']);
        $this->assertSame(20.0, $inv['amount']);
        $this->assertTrue($inv['isPaid']);
    }

    public function test_no_invoices_reports_false(): void
    {
        $b = $this->bundle();
        $b['invoices'] = [];
        $this->assertFalse((new MemberProfile($b))->hasInvoices());
    }

    public function test_next_payment_is_earliest_unpaid_invoice(): void
    {
        $p = new MemberProfile($this->bundle());
        $next = $p->nextPayment();
        $this->assertNotNull($next);
        $this->assertSame(20.0, $next['amount']);
        $this->assertSame('2026-06-15', $next['date']);
    }

    public function test_next_payment_null_when_all_paid(): void
    {
        $b = $this->bundle();
        foreach ($b['invoices'] as &$inv) { $inv['IsPaid'] = true; }
        unset($inv);
        $this->assertNull((new MemberProfile($b))->nextPayment());
    }

    public function test_last_payment_is_most_recent_payment(): void
    {
        $p = new MemberProfile($this->bundle());
        $last = $p->lastPayment();
        $this->assertNotNull($last);
        $this->assertSame(20.0, $last['amount']);
        $this->assertSame('2026-01-15', $last['date']);
    }

    public function test_paid_this_year_sums_current_year_payments(): void
    {
        $b = $this->bundle();
        // One payment in the current year, one a year earlier.
        $b['payments'] = [
            ['Id' => 1, 'Value' => 20.0, 'CreatedDate' => now()->startOfYear()->addDays(5)->toIso8601String()],
            ['Id' => 2, 'Value' => 60.0, 'CreatedDate' => now()->subYears(1)->toIso8601String()],
        ];
        $p = new MemberProfile($b);
        $this->assertSame(20.0, $p->paidThisYear());
        $this->assertSame(80.0, $p->paidAllTime());
    }

    public function test_payment_totals_zero_when_no_payments(): void
    {
        $b = $this->bundle();
        $b['payments'] = [];
        $p = new MemberProfile($b);
        $this->assertSame(0.0, $p->paidThisYear());
        $this->assertSame(0.0, $p->paidAllTime());
        $this->assertNull($p->lastPayment());
    }

    public function test_next_payment_skips_unpaid_invoice_with_missing_date(): void
    {
        $b = $this->bundle();
        // An unpaid invoice with no CreatedDate must not be chosen as next payment.
        $b['invoices'] = [
            ['Id' => 9, 'DocumentNumber' => 'INV-NODATE', 'Value' => 99.0, 'IsPaid' => false],
            ['Id' => 2, 'DocumentNumber' => 'INV-2026-0002', 'Value' => 20.0, 'IsPaid' => false, 'CreatedDate' => '2026-06-15T00:00:00'],
        ];
        $next = (new MemberProfile($b))->nextPayment();
        $this->assertNotNull($next);
        $this->assertSame('2026-06-15', $next['date']);
        $this->assertSame(20.0, $next['amount']);
    }

    public function test_billing_period_annual_adds_one_year(): void
    {
        $b = $this->bundle();
        $b['contact']['MembershipLevel'] = ['Name' => 'Individual Membership'];
        $p = new MemberProfile($b);

        $period = $p->billingPeriod(['date' => '2026-01-15']);
        $this->assertSame('Jan 2026 – Jan 2027', $period);
    }

    public function test_billing_period_checkomatic_adds_one_month(): void
    {
        $b = $this->bundle();
        $b['contact']['MembershipLevel'] = ['Name' => 'Checkomatic'];
        $p = new MemberProfile($b);

        $period = $p->billingPeriod(['date' => '2026-01-15']);
        $this->assertSame('Jan 2026 – Feb 2026', $period);
    }

    public function test_billing_period_lifetime_is_blank(): void
    {
        $b = $this->bundle();
        $b['contact']['MembershipLevel'] = ['Name' => 'Lifetime'];
        $p = new MemberProfile($b);

        $this->assertSame('', $p->billingPeriod(['date' => '2026-01-15']));
    }

    public function test_billing_period_blank_for_missing_date(): void
    {
        $p = new MemberProfile($this->bundle());
        $this->assertSame('', $p->billingPeriod(['date' => '']));
        $this->assertSame('', $p->billingPeriod([]));
    }

    public function test_billing_period_blank_for_unparseable_date(): void
    {
        $b = $this->bundle();
        $b['contact']['MembershipLevel'] = ['Name' => 'Individual Membership'];
        $p = new MemberProfile($b);

        $this->assertSame('', $p->billingPeriod(['date' => 'not-a-date']));
    }
}

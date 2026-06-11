<?php

namespace Tests\Unit;

use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
use App\Services\RenewalService;
use App\Support\MemberProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RenewalServiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_fee_for_standard_individual_plan(): void
    {
        $svc = app(RenewalService::class);
        $fee = $svc->resolveFee('individual', 0, null);

        $this->assertSame(2000, $fee['cents']);
        $this->assertSame('$20.00', $fee['label']);
    }

    public function test_fee_for_flat_plan_multiplies_by_member_count(): void
    {
        $svc = app(RenewalService::class);
        // 1 primary + 3 family members = 4 * $20 = $80.
        $fee = $svc->resolveFee('flat', 3, null);

        $this->assertSame(8000, $fee['cents']);
        $this->assertSame('$80.00', $fee['label']);
    }

    public function test_fee_for_checkomatic_uses_monthly_amount(): void
    {
        $svc = app(RenewalService::class);
        $fee = $svc->resolveFee('checkomatic_individual', 0, 15.50);

        $this->assertSame(1550, $fee['cents']);
        $this->assertSame('$15.50/mo', $fee['label']);
    }

    public function test_build_summary_shape(): void
    {
        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999,
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'],
            'FieldValues' => [],
        ]]);

        $summary = $svc->buildSummary($profile);

        $this->assertSame('individual', $summary['type']);
        $this->assertFalse($summary['isCheckomatic']);
        $this->assertSame(2000, $summary['fee']['cents']);
        $this->assertArrayHasKey('newRenewalDate', $summary);
        $this->assertSame(0, $summary['familyCount']);
    }

    public function test_new_renewal_date_is_end_of_next_calendar_year(): void
    {
        $svc = app(RenewalService::class);
        $expected = now()->addYear()->endOfYear()->format('m-d-Y');

        $this->assertSame($expected, $svc->newRenewalDate('individual'));
    }

    public function test_new_renewal_date_iso_matches_human_date(): void
    {
        $svc = app(RenewalService::class);

        // The ISO form must represent the same calendar day as the human form.
        $this->assertSame(
            now()->addYear()->endOfYear()->format('Y-m-d'),
            \Carbon\Carbon::parse($svc->newRenewalDateIso('individual'))->format('Y-m-d')
        );
    }

    public function test_new_renewal_date_for_checkomatic_is_one_month_out(): void
    {
        $svc = app(RenewalService::class);
        $expected = now()->addMonth()->format('m-d-Y');

        $this->assertSame($expected, $svc->newRenewalDate('checkomatic_individual'));
    }

    public function test_charge_success_creates_paid_renewal_and_dispatches_job(): void
    {
        Queue::fake();

        // Mock StripeService so no real Stripe calls happen.
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(
            \Stripe\Customer::constructFrom(['id' => 'cus_test'])
        );
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom([
                'type' => 'card',
                'card' => ['brand' => 'visa', 'last4' => '4242'],
            ])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test'])
        );
        $stripe->shouldReceive('processPayment')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test', 'status' => 'succeeded', 'latest_charge' => 'ch_test'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'pm_test_123', null);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('renewal_id', $result);
        $this->assertDatabaseHas('renewals', ['id' => $result['renewal_id'], 'status' => 'paid']);
        Queue::assertPushed(ProcessMembershipRenewal::class);
    }

    public function test_charge_requires_action_returns_client_secret(): void
    {
        Queue::fake();

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(
            \Stripe\Customer::constructFrom(['id' => 'cus_test'])
        );
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom([
                'type' => 'card',
                'card' => ['brand' => 'visa', 'last4' => '4242'],
            ])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test'])
        );
        $stripe->shouldReceive('processPayment')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test', 'status' => 'requires_action', 'client_secret' => 'pi_test_secret'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'pm_test_123', null);

        $this->assertTrue($result['requires_action']);
        $this->assertSame('pi_test_secret', $result['client_secret']);
        Queue::assertNotPushed(ProcessMembershipRenewal::class);
    }

    public function test_charge_declined_card_marks_renewal_failed_with_decline_code(): void
    {
        Queue::fake();

        // Build a real Stripe CardException carrying a decline code.
        $stripeError = \Stripe\ErrorObject::constructFrom([
            'type'         => 'card_error',
            'code'         => 'card_declined',
            'decline_code' => 'insufficient_funds',
            'message'      => 'Your card has insufficient funds.',
        ]);
        $cardException = new \Stripe\Exception\CardException('Your card has insufficient funds.');
        $cardException->setError($stripeError);

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(
            \Stripe\Customer::constructFrom(['id' => 'cus_test'])
        );
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom([
                'type' => 'card',
                'card' => ['brand' => 'visa', 'last4' => '4242'],
            ])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test'])
        );
        $stripe->shouldReceive('processPayment')->andThrow($cardException);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'pm_test_123', null);

        $this->assertFalse($result['success']);
        $this->assertSame('Your card has insufficient funds.', $result['message']);
        $this->assertDatabaseHas('renewals', [
            'contact_id'         => 999,
            'status'             => 'failed',
            'error_decline_code' => 'insufficient_funds',
        ]);
        Queue::assertNotPushed(\App\Jobs\ProcessMembershipRenewal::class);
    }

    public function test_charge_rejects_checkomatic_renewal_with_no_amount(): void
    {
        Queue::fake();

        // Stripe must never be called — a mock with no expectations would fail
        // loudly if charge() proceeded past the guard.
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(RenewalService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'cm@example.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Checkomatic'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'pm_test', null);

        $this->assertFalse($result['success']);
        $this->assertSame('Please enter your monthly contribution amount.', $result['message']);
        $this->assertDatabaseCount('renewals', 0);
        Queue::assertNotPushed(\App\Jobs\ProcessMembershipRenewal::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

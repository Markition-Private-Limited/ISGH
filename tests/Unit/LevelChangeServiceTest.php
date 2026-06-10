<?php

namespace Tests\Unit;

use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use App\Services\LevelChangeService;
use App\Support\MemberProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class LevelChangeServiceTest extends TestCase
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

    public function test_available_levels_returns_allowlisted_types_only(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $types = array_column($levels, 'type');
        // individual is the current level — excluded
        $this->assertNotContains('individual', $types, 'current type excluded');
        // these were previously offered but are now removed
        $this->assertNotContains('family', $types, 'family no longer a change target');
        $this->assertNotContains('flat', $types, 'flat never a change target');
        $this->assertNotContains('checkomatic_family', $types, 'checkomatic_family never a change target');
        // the 3 remaining allowed slugs
        $this->assertContains('checkomatic_individual', $types);
        $this->assertContains('lifetime_family', $types);
        $this->assertContains('lifetime_individual', $types);
        $this->assertCount(3, $levels);
    }

    public function test_available_levels_checkomatic_label_and_flags(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $byType = array_column($levels, null, 'type');

        // Checkomatic label must be 'Checkomatic' (not 'Checkomatic Individual')
        $this->assertSame('Checkomatic', $byType['checkomatic_individual']['label']);
        // includesFamily must be true so the JS shows the optional spouse screen
        $this->assertTrue($byType['checkomatic_individual']['includesFamily']);
        $this->assertTrue($byType['checkomatic_individual']['isCheckomatic']);
        $this->assertArrayHasKey('fee', $byType['checkomatic_individual']);
        // Lifetime individual has no family
        $this->assertFalse($byType['lifetime_individual']['includesFamily']);
    }

    public function test_available_levels_excludes_current_when_checkomatic(): void
    {
        $svc = app(LevelChangeService::class);
        // Member is already on checkomatic_individual — it should not appear
        $levels = $svc->availableLevels(
            $this->profileWithLevel('Checkomatic')
        );
        $types = array_column($levels, 'type');
        $this->assertNotContains('checkomatic_individual', $types);
        $this->assertCount(3, $levels); // individual, lifetime_family, lifetime_individual
    }

    private function mockStripeSuccess(): void
    {
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(
            \Stripe\Customer::constructFrom(['id' => 'cus_test'])
        );
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom(['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test'])
        );
        $stripe->shouldReceive('processPayment')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test', 'status' => 'succeeded', 'latest_charge' => 'ch_test'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);
    }

    public function test_charge_success_creates_paid_level_change_and_dispatches_job(): void
    {
        Queue::fake();
        $this->mockStripeSuccess();

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'family', [], 'pm_test', null);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('level_changes', [
            'id'        => $result['level_change_id'],
            'from_type' => 'individual',
            'to_type'   => 'family',
            'status'    => 'paid',
        ]);
        Queue::assertPushed(ProcessLevelChange::class);
    }

    public function test_charge_rejects_changing_to_the_same_type(): void
    {
        Queue::fake();
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'a@b.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'individual', [], 'pm_test', null);

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('level_changes', 0);
        Queue::assertNotPushed(ProcessLevelChange::class);
    }

    public function test_charge_rejects_checkomatic_with_no_amount(): void
    {
        Queue::fake();
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'a@b.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'checkomatic_individual', [], 'pm_test', null);

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('level_changes', 0);
    }

    public function test_charge_declined_card_marks_failed_with_decline_code(): void
    {
        Queue::fake();

        $stripeError = \Stripe\ErrorObject::constructFrom([
            'type'         => 'card_error',
            'code'         => 'card_declined',
            'decline_code' => 'insufficient_funds',
            'message'      => 'Your card has insufficient funds.',
        ]);
        $cardException = new \Stripe\Exception\CardException('Your card has insufficient funds.');
        $cardException->setError($stripeError);

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(\Stripe\Customer::constructFrom(['id' => 'cus_test']));
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom(['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(\Stripe\PaymentIntent::constructFrom(['id' => 'pi_test']));
        $stripe->shouldReceive('processPayment')->andThrow($cardException);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'family', [], 'pm_test', null);

        $this->assertFalse($result['success']);
        $this->assertSame('Your card has insufficient funds.', $result['message']);
        $this->assertDatabaseHas('level_changes', [
            'contact_id'         => 999,
            'status'             => 'failed',
            'error_decline_code' => 'insufficient_funds',
        ]);
        Queue::assertNotPushed(ProcessLevelChange::class);
    }

    public function test_charge_requires_action_returns_client_secret(): void
    {
        Queue::fake();

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(\Stripe\Customer::constructFrom(['id' => 'cus_test']));
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom(['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(\Stripe\PaymentIntent::constructFrom(['id' => 'pi_test']));
        $stripe->shouldReceive('processPayment')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_test', 'status' => 'requires_action', 'client_secret' => 'pi_test_secret'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $svc = app(LevelChangeService::class);
        $profile = new MemberProfile(['contact' => [
            'Id' => 999, 'Email' => 'tauqeer@example.com',
            'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
        ]]);

        $result = $svc->charge(999, $profile, 'family', [], 'pm_test', null);

        $this->assertTrue($result['requires_action']);
        $this->assertSame('pi_test_secret', $result['client_secret']);
        $this->assertArrayHasKey('level_change_id', $result);
        // The row stays pending until 3DS is finalized; the job is not dispatched.
        $this->assertDatabaseHas('level_changes', ['id' => $result['level_change_id'], 'status' => 'pending']);
        Queue::assertNotPushed(ProcessLevelChange::class);
    }

    public function test_finalize_marks_paid_and_dispatches_job(): void
    {
        Queue::fake();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'pending',
            'stripe_payment_intent_id' => 'pi_3ds', 'family_members' => [],
        ]);

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('getPaymentIntent')->with('pi_3ds')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_3ds', 'status' => 'succeeded', 'latest_charge' => 'ch_3ds'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $result = app(LevelChangeService::class)->finalize($lc->id, 'pi_3ds');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('level_changes', ['id' => $lc->id, 'status' => 'paid']);
        Queue::assertPushed(ProcessLevelChange::class);
    }

    public function test_finalize_is_idempotent_for_already_paid(): void
    {
        Queue::fake();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'paid',
            'stripe_payment_intent_id' => 'pi_3ds', 'family_members' => [],
        ]);

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $result = app(LevelChangeService::class)->finalize($lc->id, 'pi_3ds');

        $this->assertTrue($result['success']);
        Queue::assertNotPushed(ProcessLevelChange::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

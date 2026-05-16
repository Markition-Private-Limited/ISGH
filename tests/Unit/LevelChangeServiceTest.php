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

    public function test_available_levels_excludes_current_type(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $types = array_column($levels, 'type');
        $this->assertNotContains('individual', $types, 'current type excluded');
        $this->assertCount(6, $levels);
    }

    public function test_available_levels_flag_family_and_checkomatic(): void
    {
        $svc = app(LevelChangeService::class);
        $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

        $byType = [];
        foreach ($levels as $l) {
            $byType[$l['type']] = $l;
        }

        $this->assertTrue($byType['family']['includesFamily']);
        $this->assertFalse($byType['lifetime_individual']['includesFamily']);
        $this->assertTrue($byType['checkomatic_individual']['isCheckomatic']);
        $this->assertArrayHasKey('fee', $byType['family']);
        $this->assertArrayHasKey('label', $byType['family']);
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

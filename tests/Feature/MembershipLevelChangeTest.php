<?php

namespace Tests\Feature;

use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class MembershipLevelChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/membershiplevels' => Http::response([
                ['Id' => 1, 'Name' => 'Individual', 'MembershipFee' => 25.0],
            ], 200),
        ]);
    }

    public function test_change_level_options_excludes_current_type(): void
    {
        $res = $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson('/member-portal/change-level/options')
          ->assertOk()
          ->assertJsonPath('success', true)
          ->json();

        $types = array_column($res['levels'], 'type');
        $this->assertNotContains('individual', $types);
        // Allowlist is now: checkomatic_family, individual, lifetime_family, lifetime_individual
        // Member is 'Individual' → 3 options remain.
        $this->assertContains('checkomatic_family', $types);
        $this->assertContains('lifetime_family', $types);
        $this->assertContains('lifetime_individual', $types);
        $this->assertCount(3, $types);
    }

    public function test_process_level_change_charges_and_creates_row(): void
    {
        Queue::fake();

        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCustomer')->andReturn(\Stripe\Customer::constructFrom(['id' => 'cus_t']));
        $stripe->shouldReceive('addPaymentMethodToCustomer')->andReturn(
            \Stripe\PaymentMethod::constructFrom(['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']])
        );
        $stripe->shouldReceive('createPaymentIntent')->andReturn(\Stripe\PaymentIntent::constructFrom(['id' => 'pi_t']));
        $stripe->shouldReceive('processPayment')->andReturn(
            \Stripe\PaymentIntent::constructFrom(['id' => 'pi_t', 'status' => 'succeeded', 'latest_charge' => 'ch_t'])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->postJson('/member-portal/change-level', [
            'target_type'       => 'family',
            'payment_method_id' => 'pm_t',
            'family_members'    => [['first_name' => 'Sarah', 'last_name' => 'Alam']],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('level_changes', ['contact_id' => 999, 'to_type' => 'family', 'status' => 'paid']);
        Queue::assertPushed(ProcessLevelChange::class);
    }

    public function test_process_level_change_rejects_same_type(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->postJson('/member-portal/change-level', [
            'target_type'       => 'individual',
            'payment_method_id' => 'pm_t',
        ])->assertStatus(402);
    }

    public function test_level_change_status_reflects_state(): void
    {
        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'processed',
            'processed' => true, 'wa_invoice_id' => 555, 'family_members' => [],
        ]);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson("/member-portal/change-level/status/{$lc->id}")
          ->assertOk()
          ->assertJson(['processed' => true, 'wa_invoice_id' => 555]);
    }

    public function test_level_change_status_rejects_another_member(): void
    {
        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'processed',
            'processed' => true, 'family_members' => [],
        ]);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->getJson("/member-portal/change-level/status/{$lc->id}")
          ->assertStatus(404);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

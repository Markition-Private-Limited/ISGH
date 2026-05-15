<?php

namespace Tests\Feature;

use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class MembershipRenewalTest extends TestCase
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
                'Email' => 'tauqeer@example.com', 'Status' => 'Lapsed',
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

    public function test_renew_summary_returns_fee_and_renewal_date(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson('/member-portal/renew/summary')
          ->assertOk()
          ->assertJson(['renewable' => true, 'type' => 'individual'])
          ->assertJsonPath('fee.cents', 2500);
    }

    public function test_process_renewal_charges_and_creates_renewal(): void
    {
        Queue::fake();

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

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->postJson('/member-portal/renew', ['payment_method_id' => 'pm_test_123'])
          ->assertOk()
          ->assertJson(['success' => true]);

        $this->assertDatabaseHas('renewals', ['contact_id' => 999, 'status' => 'paid']);
        Queue::assertPushed(ProcessMembershipRenewal::class);
    }

    public function test_renew_status_reflects_renewal_state(): void
    {
        $renewal = Renewal::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'membership_type' => 'individual', 'amount_cents' => 2500,
            'currency' => 'usd', 'status' => 'processed', 'processed' => true,
            'wa_invoice_id' => 555,
        ]);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->getJson("/member-portal/renew/status/{$renewal->id}")
          ->assertOk()
          ->assertJson(['processed' => true, 'wa_invoice_id' => 555]);
    }

    public function test_member_cannot_read_another_members_renewal_status(): void
    {
        // A renewal that belongs to contact 999.
        $renewal = Renewal::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'membership_type' => 'individual', 'amount_cents' => 2500,
            'currency' => 'usd', 'status' => 'processed', 'processed' => true,
            'wa_invoice_id' => 555,
        ]);

        // A different member (contact 888) must not be able to read it.
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->getJson("/member-portal/renew/status/{$renewal->id}")
          ->assertStatus(404);
    }

    public function test_lifetime_member_cannot_renew(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/888' => Http::response([
                'Id' => 888, 'FirstName' => 'Life', 'LastName' => 'Member',
                'Email' => 'life@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 9, 'Name' => 'Lifetime'], 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
        ]);

        // Summary reports not-renewable.
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->getJson('/member-portal/renew/summary')
          ->assertOk()
          ->assertJson(['renewable' => false]);

        // The charge endpoint rejects it with 422.
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->postJson('/member-portal/renew', ['payment_method_id' => 'pm_test'])
          ->assertStatus(422);
    }

    public function test_finalize_renewal_marks_paid_and_dispatches_job(): void
    {
        Queue::fake();

        // A pending renewal awaiting 3DS, owned by the session member (999).
        $renewal = Renewal::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'membership_type' => 'individual', 'amount_cents' => 2500,
            'currency' => 'usd', 'status' => 'pending',
            'stripe_payment_intent_id' => 'pi_3ds',
        ]);

        // Mock Stripe: the intent is now succeeded after the 3DS challenge.
        $stripe = Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('getPaymentIntent')->with('pi_3ds')->andReturn(
            \Stripe\PaymentIntent::constructFrom([
                'id' => 'pi_3ds', 'status' => 'succeeded', 'latest_charge' => 'ch_3ds',
            ])
        );
        $this->app->instance(\App\Services\StripeService::class, $stripe);

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
        ])->postJson('/member-portal/renew/finalize', [
            'renewal_id'        => $renewal->id,
            'payment_intent_id' => 'pi_3ds',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('renewals', ['id' => $renewal->id, 'status' => 'paid']);
        Queue::assertPushed(ProcessMembershipRenewal::class);
    }

    public function test_finalize_renewal_rejects_another_members_renewal(): void
    {
        $renewal = Renewal::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'membership_type' => 'individual', 'amount_cents' => 2500,
            'currency' => 'usd', 'status' => 'pending',
            'stripe_payment_intent_id' => 'pi_3ds',
        ]);

        // A different member (888) must not be able to finalize contact 999's renewal.
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 888,
        ])->postJson('/member-portal/renew/finalize', [
            'renewal_id'        => $renewal->id,
            'payment_intent_id' => 'pi_3ds',
        ])->assertStatus(404);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

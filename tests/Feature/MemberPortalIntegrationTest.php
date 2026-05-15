<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberPortalIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);

        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
        ]);
    }

    public function test_verify_otp_assembles_and_caches_bundle(): void
    {
        $this->withSession(['member_portal_otp' => [
            'code' => '123456', 'email' => 'tauqeer@example.com',
            'expires_at' => now()->addMinutes(5)->timestamp, 'contact_id' => 999,
        ]]);

        $res = $this->postJson('/member-portal/verify-otp', ['otp' => '123456']);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(Cache::has('member_portal_bundle_999'));
    }

    public function test_dashboard_renders_member_data(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/dashboard')
            ->assertOk()
            ->assertSee('Tauqeer Alam')
            ->assertSee('Individual Membership');
    }

    public function test_profile_renders_member_data(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/profile')
            ->assertOk()
            ->assertSee('Tauqeer');
    }
}

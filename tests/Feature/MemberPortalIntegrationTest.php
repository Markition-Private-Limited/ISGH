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
            ->assertSee('Tauqeer Alam')
            ->assertSee('tauqeer@example.com')
            ->assertSee('Individual Membership');
    }

    public function test_update_profile_persists_and_invalidates_cache(): void
    {
        // updateMember() does one GET (fetch current) then one PUT (update) — 2 hits.
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::sequence()
                ->push(['Id' => 999, 'FirstName' => 'Tauqeer', 'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => []], 200)
                ->push(['Id' => 999, 'FirstName' => 'Tariq', 'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*'  => Http::response(['Invoices' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*'  => Http::response(['Payments' => []], 200),
        ]);

        Cache::put('member_portal_bundle_999', ['contact' => ['Id' => 999], 'family' => [], 'invoices' => [], 'payments' => []], now()->addMinutes(10));

        $this->withSession(['member_portal_authenticated' => true, 'member_portal_contact_id' => 999])
            ->postJson('/member-portal/profile/update', [
                'first_name' => 'Tariq', 'last_name' => 'Alam',
                'email' => 'tariq@example.com', 'phone' => '2154389281',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse(Cache::has('member_portal_bundle_999'));

        // The edited name must have reached WildApricot in the PUT body.
        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_contains($request->url(), '/contacts/999')
                && collect($request['FieldValues'] ?? [])->contains(
                    fn ($fv) => ($fv['FieldName'] ?? '') === 'FirstName' && ($fv['Value'] ?? '') === 'Tariq'
                );
        });
    }

    public function test_update_profile_rejects_invalid_email(): void
    {
        $this->withSession(['member_portal_authenticated' => true, 'member_portal_contact_id' => 999])
            ->postJson('/member-portal/profile/update', [
                'first_name' => 'Tariq', 'last_name' => 'Alam', 'email' => 'not-an-email',
            ])
            ->assertStatus(422);
    }

    public function test_payments_page_renders_invoice_history(): void
    {
        // Seed the cache directly so setUp's Http stubs (which return empty invoices)
        // are never called — getBundle() will hit the cache and return this bundle.
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family'   => [],
            'invoices' => [
                ['Id' => 1, 'DocumentNumber' => 'INV-2026-0001', 'Value' => 20.0,
                 'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00', 'Url' => 'https://wa.test/inv/1'],
            ],
            'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/payments')
            ->assertOk()
            ->assertSee('Payments and Invoice')
            ->assertSee('INV-2026-0001');
    }

    public function test_payments_page_requires_authentication(): void
    {
        $this->get('/member-portal/payments')
            ->assertRedirect('/member-portal/login');
    }

    public function test_payments_page_shows_empty_state_when_no_invoices(): void
    {
        // Seed the cache with a bundle that has no invoices.
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family'   => [],
            'invoices' => [],
            'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/payments')
            ->assertOk()
            ->assertSee('No invoices yet');
    }

    public function test_payments_page_renders_disabled_view_link_when_invoice_url_missing(): void
    {
        // Invoice has no 'Url' key — MemberProfile defaults url to '#', so the
        // view must render a disabled <span> instead of an <a> link.
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family'   => [],
            'invoices' => [
                ['Id' => 7, 'DocumentNumber' => 'INV-NOURL', 'Value' => 25.0,
                 'IsPaid' => true, 'CreatedDate' => '2026-02-01T00:00:00'],
            ],
            'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/payments')
            ->assertOk()
            ->assertSee('INV-NOURL')
            ->assertSee('inv-view disabled', false);
    }

    /**
     * Every authenticated member-portal page renders 200 with the sidebar
     * (which now includes the logout button) present.
     */
    public function test_all_member_portal_pages_render_with_logout_button(): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => [], 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        foreach ([
            '/member-portal/dashboard',
            '/member-portal/profile',
            '/member-portal/payments',
            '/member-portal/records',
            '/member-portal/newsletter',
            '/member-portal/financial-report',
            '/member-portal/updates',
            '/member-portal/nominees-training',
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Logout')
                ->assertSee(route('member-portal.logout'), false);
        }
    }

    public function test_logout_clears_session_and_redirects_to_login(): void
    {
        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->post('/member-portal/logout')
            ->assertRedirect('/member-portal/login');

        // After logout the protected area must bounce back to login.
        $this->get('/member-portal/dashboard')
            ->assertRedirect('/member-portal/login');
    }

    public function test_profile_page_includes_renewal_and_level_modals(): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => [], 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/profile')
            ->assertOk()
            ->assertSee('id="renewModal"', false)
            ->assertSee('id="levelModal"', false)
            ->assertSee('ql-renew-link', false)
            ->assertSee('ql-change-level', false);
    }

    public function test_dashboard_still_includes_both_modals_after_extraction(): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => [], 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/dashboard')
            ->assertOk()
            ->assertSee('id="renewModal"', false)
            ->assertSee('id="levelModal"', false);
    }

    public function test_lifetime_member_profile_omits_renewal_modal(): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Lifetime Membership (Individual)'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => [], 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);

        $this->get('/member-portal/profile')
            ->assertOk()
            ->assertSee('id="levelModal"', false)
            ->assertDontSee('id="renewModal"', false);
    }
}

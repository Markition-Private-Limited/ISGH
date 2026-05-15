<?php

namespace Tests\Feature;

use App\Services\MemberPortalService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberPortalServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
    }

    private function fakeWaSuccess(): void
    {
        Http::fake([
            // getFamilyMembers() first resolves the "Member Identifier" custom
            // field's system code via the contactfields endpoint.
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Status' => 'Active', 'MembershipLevel' => ['Name' => 'Individual Membership'],
                'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response([
                'Contacts' => [['Id' => 1001, 'FirstName' => 'Sarah', 'LastName' => 'Alam', 'FieldValues' => []]],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*' => Http::response([
                'Invoices' => [['Id' => 1, 'DocumentNumber' => 'INV-1', 'Value' => 20.0, 'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00']],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments*' => Http::response([
                'Payments' => [['Id' => 5, 'Value' => 20.0, 'CreatedDate' => '2026-01-15T00:00:00']],
            ], 200),
        ]);
    }

    public function test_assemble_bundle_aggregates_all_slices(): void
    {
        $this->fakeWaSuccess();

        $bundle = app(MemberPortalService::class)->assembleBundle(999);

        $this->assertSame('Tauqeer', $bundle['contact']['FirstName']);
        $this->assertCount(1, $bundle['family']);
        $this->assertCount(1, $bundle['invoices']);
        $this->assertCount(1, $bundle['payments']);
    }

    public function test_assemble_bundle_caches_result(): void
    {
        $this->fakeWaSuccess();

        app(MemberPortalService::class)->assembleBundle(999);

        $this->assertTrue(Cache::has('member_portal_bundle_999'));
    }

    public function test_bundle_survives_failed_invoices_call(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'FirstName' => 'Tauqeer', 'FieldValues' => [],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/invoices*' => Http::response('error', 500),
            'api.wildapricot.org/v2.3/accounts/12345/payments*' => Http::response(['Payments' => []], 200),
        ]);

        $bundle = app(MemberPortalService::class)->assembleBundle(999);

        $this->assertSame('Tauqeer', $bundle['contact']['FirstName']);
        $this->assertSame([], $bundle['invoices']);
    }

    public function test_get_bundle_returns_cached_without_refetch(): void
    {
        $this->fakeWaSuccess();
        $svc = app(MemberPortalService::class);
        $svc->assembleBundle(999);
        Http::fake(); // any further call would now return empty/throw

        $bundle = $svc->getBundle(999);
        $this->assertSame('Tauqeer', $bundle['contact']['FirstName']);
    }
}

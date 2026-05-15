<?php

namespace Tests\Feature;

use App\Services\WildApricotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WildApricotFetchTest extends TestCase
{
    private function service(): WildApricotService
    {
        // Pre-seed auth token + account id so no auth/account calls are needed.
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
        return new WildApricotService();
    }

    public function test_get_invoices_for_contact_returns_invoice_array(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices*' => Http::response([
                'Invoices' => [
                    ['Id' => 1, 'DocumentNumber' => 'INV-2026-0001', 'Value' => 20.0, 'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
                ],
            ], 200),
        ]);

        $invoices = $this->service()->getInvoicesForContact(999);

        $this->assertCount(1, $invoices);
        $this->assertSame('INV-2026-0001', $invoices[0]['DocumentNumber']);
    }

    public function test_get_payments_for_contact_returns_payment_array(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/payments*' => Http::response([
                'Payments' => [
                    ['Id' => 5, 'Value' => 20.0, 'CreatedDate' => '2026-01-15T00:00:00'],
                ],
            ], 200),
        ]);

        $payments = $this->service()->getPaymentsForContact(999);

        $this->assertCount(1, $payments);
        // JSON round-trips 20.0 as 20, so compare by value not type.
        $this->assertEquals(20.0, $payments[0]['Value']);
    }

    public function test_get_family_members_returns_contacts_array(): void
    {
        Http::fake([
            // getFamilyMembers() first resolves the "Member Identifier" field code
            // via /contactfields — fake that so no real network call is made.
            'api.wildapricot.org/v2.3/accounts/12345/contactfields*' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts*' => Http::response([
                'Contacts' => [
                    ['Id' => 1001, 'FirstName' => 'Sarah', 'LastName' => 'Alam', 'FieldValues' => []],
                ],
            ], 200),
        ]);

        $family = $this->service()->getFamilyMembers(999);

        $this->assertCount(1, $family);
        $this->assertSame('Sarah', $family[0]['FirstName']);
    }

    public function test_fetch_methods_return_empty_array_on_api_failure(): void
    {
        Http::fake(['api.wildapricot.org/*' => Http::response('error', 500)]);

        $svc = $this->service();
        $this->assertSame([], $svc->getInvoicesForContact(999));
        $this->assertSame([], $svc->getPaymentsForContact(999));
        $this->assertSame([], $svc->getFamilyMembers(999));
    }
}

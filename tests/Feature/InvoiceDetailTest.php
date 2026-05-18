<?php

namespace Tests\Feature;

use App\Services\WildApricotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoiceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
    }

    public function test_get_invoice_by_id_returns_invoice_array(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/156' => Http::response([
                'Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
                'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00',
            ], 200),
        ]);

        $invoice = app(WildApricotService::class)->getInvoiceById(156);

        $this->assertSame('INV-2025-0156', $invoice['DocumentNumber']);
        $this->assertSame(156, $invoice['Id']);
    }

    public function test_get_invoice_by_id_returns_null_on_api_error(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/999' => Http::response([], 404),
        ]);

        $this->assertNull(app(WildApricotService::class)->getInvoiceById(999));
    }

    /** Seed a member bundle in cache and authenticate the session for contact 999. */
    private function seedMember(array $invoices = []): void
    {
        Cache::put('member_portal_bundle_999', [
            'contact'  => [
                'Id' => 999, 'FirstName' => 'Tauqeer', 'LastName' => 'Alam',
                'Email' => 'tauqeer@example.com', 'Status' => 'Active',
                'MembershipLevel' => ['Name' => 'Individual Membership'], 'FieldValues' => [],
            ],
            'family' => [], 'invoices' => $invoices, 'payments' => [],
        ], now()->addMinutes(10));

        $this->withSession([
            'member_portal_authenticated' => true,
            'member_portal_contact_id'    => 999,
            'member_portal_email'         => 'tauqeer@example.com',
        ]);
    }

    public function test_invoice_detail_requires_authentication(): void
    {
        $this->getJson('/member-portal/invoice/156')
            ->assertStatus(401);
    }

    public function test_invoice_detail_rejects_invoice_not_owned_by_member(): void
    {
        // Member owns invoice 156 only; requesting 777 must 404 without ever
        // calling WildApricot.
        $this->seedMember([
            ['Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
             'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
        ]);

        $this->getJson('/member-portal/invoice/777')
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_invoice_detail_returns_paid_invoice_with_payment(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/156' => Http::response([
                'Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
                'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00',
            ], 200),
        ]);

        \App\Models\Renewal::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'membership_type' => 'individual', 'amount_cents' => 2000, 'currency' => 'usd',
            'status' => 'succeeded', 'wa_invoice_id' => 156, 'processed' => true,
            'payment_method' => 'card', 'card_brand' => 'visa', 'card_last4' => '4242',
            'paid_at' => '2026-01-15 10:00:00',
        ]);

        $this->seedMember([
            ['Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
             'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
        ]);

        $res = $this->getJson('/member-portal/invoice/156')->assertOk();

        $res->assertJson([
            'success' => true,
            'invoice' => [
                'number'         => 'INV-2025-0156',
                'isPaid'         => true,
                'memberName'     => 'Tauqeer Alam',
                'membershipType' => 'Individual Membership',
            ],
        ]);
        $this->assertStringContainsString('4242', $res->json('invoice.payment.method'));
    }

    public function test_invoice_detail_omits_payment_for_unpaid_invoice(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices/200' => Http::response([
                'Id' => 200, 'DocumentNumber' => 'INV-2025-0200', 'Value' => 30.0,
                'IsPaid' => false, 'CreatedDate' => '2026-02-01T00:00:00',
            ], 200),
        ]);

        $this->seedMember([
            ['Id' => 200, 'DocumentNumber' => 'INV-2025-0200', 'Value' => 30.0,
             'IsPaid' => false, 'CreatedDate' => '2026-02-01T00:00:00'],
        ]);

        $this->getJson('/member-portal/invoice/200')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'invoice' => ['isPaid' => false, 'status' => 'Unpaid', 'payment' => null],
            ]);
    }

    public function test_payments_page_renders_invoice_modal_and_view_buttons(): void
    {
        $this->seedMember([
            ['Id' => 156, 'DocumentNumber' => 'INV-2025-0156', 'Value' => 20.0,
             'IsPaid' => true, 'CreatedDate' => '2026-01-15T00:00:00'],
        ]);

        $this->get('/member-portal/payments')
            ->assertOk()
            ->assertSee('id="invoiceModal"', false)
            ->assertSee('data-invoice-id="156"', false);
    }
}

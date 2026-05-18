<?php

namespace Tests\Feature;

use App\Services\WildApricotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoiceDetailTest extends TestCase
{
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
}

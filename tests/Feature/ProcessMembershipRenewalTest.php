<?php

namespace Tests\Feature;

use App\Jobs\ProcessMembershipRenewal;
use App\Models\Renewal;
use App\Services\WildApricotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessMembershipRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);
    }

    private function fakeWa(): void
    {
        Http::fake([
            'api.wildapricot.org/v2.3/accounts/12345/invoices' => Http::response(['Id' => 555], 200),
            'api.wildapricot.org/v2.3/accounts/12345/payments' => Http::response(['Id' => 777], 200),
            'api.wildapricot.org/v2.3/accounts/12345/paymentsystemtenders*' => Http::response([
                ['Id' => 3, 'Name' => 'Stripe'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/membershiplevels' => Http::response([
                ['Id' => 1, 'Name' => 'Individual', 'MembershipFee' => 25.0],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts?*' => Http::response(['Contacts' => []], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
            ], 200),
        ]);
    }

    private function makeRenewal(): Renewal
    {
        return Renewal::create([
            'contact_id'      => 999,
            'member_email'    => 'tauqeer@example.com',
            'membership_type' => 'individual',
            'amount_cents'    => 2500,
            'currency'        => 'usd',
            'status'          => 'paid',
            'stripe_charge_id'=> 'ch_test_123',
            'stripe_payment_method_id' => 'pm_test_123',
        ]);
    }

    public function test_job_creates_invoice_records_payment_and_marks_processed(): void
    {
        $this->fakeWa();
        $renewal = $this->makeRenewal();

        (new ProcessMembershipRenewal($renewal))->handle(app(WildApricotService::class));

        $renewal->refresh();
        $this->assertTrue($renewal->processed);
        $this->assertSame('done', $renewal->wa_step);
        $this->assertSame(555, $renewal->wa_invoice_id);
        $this->assertSame('processed', $renewal->status);

        // The job must have sent a PUT to the primary contact carrying a RenewalDue.
        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_contains($request->url(), '/contacts/999')
                && ! empty($request['RenewalDue']);
        });
    }

    public function test_job_is_idempotent_when_already_processed(): void
    {
        $this->fakeWa();
        $renewal = $this->makeRenewal();
        $renewal->update(['processed' => true, 'wa_step' => 'done']);

        (new ProcessMembershipRenewal($renewal))->handle(app(WildApricotService::class));

        $this->assertTrue($renewal->fresh()->processed);
    }
}

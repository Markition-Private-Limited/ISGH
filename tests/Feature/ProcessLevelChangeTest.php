<?php

namespace Tests\Feature;

use App\Jobs\ProcessLevelChange;
use App\Models\LevelChange;
use App\Services\WildApricotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessLevelChangeTest extends TestCase
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
            'api.wildapricot.org/v2.3/accounts/12345/membershiplevels' => Http::response([
                ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)', 'MembershipFee' => 40.0],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contactfields' => Http::response([
                ['FieldName' => 'Member Identifier', 'SystemCode' => 'custom-member-id'],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::response([
                'Id' => 999, 'Status' => 'Active',
                'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                'FieldValues' => [
                    ['FieldName' => 'Bundle ID', 'SystemCode' => 'BundleId', 'Value' => 4242],
                ],
            ], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts' => Http::response(['Id' => 1001], 200),
        ]);
    }

    public function test_job_creates_invoice_records_payment_switches_level_marks_processed(): void
    {
        $this->fakeWa();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'paid',
            'stripe_charge_id' => 'ch_test', 'family_members' => [],
        ]);

        (new ProcessLevelChange($lc))->handle(app(WildApricotService::class));

        $lc->refresh();
        $this->assertTrue($lc->processed);
        $this->assertSame('done', $lc->wa_step);
        $this->assertSame(555, $lc->wa_invoice_id);
        $this->assertSame('processed', $lc->status);
    }

    public function test_job_creates_family_members(): void
    {
        $this->fakeWa();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'tauqeer@example.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'paid',
            'stripe_charge_id' => 'ch_test',
            'family_members' => [
                ['first_name' => 'Sarah', 'last_name' => 'Alam', 'email' => 'sarah@example.com'],
            ],
        ]);

        (new ProcessLevelChange($lc))->handle(app(WildApricotService::class));

        $lc->refresh();
        $this->assertTrue($lc->processed);
        $this->assertSame([1001], $lc->created_family_ids);

        Http::assertSent(fn ($request) =>
            $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/accounts/12345/contacts')
        );
    }

    public function test_job_is_idempotent_when_already_processed(): void
    {
        $this->fakeWa();

        $lc = LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'processed',
            'processed' => true, 'wa_step' => 'done', 'family_members' => [],
        ]);

        (new ProcessLevelChange($lc))->handle(app(WildApricotService::class));

        $this->assertTrue($lc->fresh()->processed);
    }
}

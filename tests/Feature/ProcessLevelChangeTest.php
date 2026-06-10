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
                ['FieldName' => 'Zone / Center', 'SystemCode' => 'custom-9967573', 'AllowedValues' => [
                    ['Id' => 55, 'Label' => 'SW Zone'],
                ]],
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
            'zone' => 'SW Zone',
            'family_members' => [
                ['first_name' => 'Sarah', 'last_name' => 'Alam', 'email' => 'sarah@example.com'],
            ],
        ]);

        (new ProcessLevelChange($lc))->handle(app(WildApricotService::class));

        $lc->refresh();
        $this->assertTrue($lc->processed);
        $this->assertSame([1001], $lc->created_family_ids);

        // Family member POST must include the primary member's Zone/Center field.
        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST') return false;
            if (! str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/accounts/12345/contacts')) return false;
            $body = $request->data();
            $fields = $body['FieldValues'] ?? [];
            foreach ($fields as $f) {
                if (($f['SystemCode'] ?? '') === 'custom-9967573'
                    && ($f['Value']['Label'] ?? '') === 'SW Zone') {
                    return true;
                }
            }
            return false;
        });
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

    public function test_job_falls_back_to_contact_fetch_for_bundle_id(): void
    {
        Cache::put('wa_access_token', 'test-token', 1500);
        config(['services.wild_apricot.account_id' => '12345']);

        // The updateMember PUT response OMITS BundleId; the job must re-fetch
        // the contact via getContactById to obtain it.
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
                ['FieldName' => 'Zone / Center', 'SystemCode' => 'custom-9967573', 'AllowedValues' => [
                    ['Id' => 55, 'Label' => 'SW Zone'],
                ]],
            ], 200),
            // PUT (updateMember) returns a contact WITHOUT BundleId; a later GET
            // returns it. The /contacts/999 endpoint is hit 7 times before the
            // fallback getContactById: setInvoiceNumberOnContact (GET+PUT),
            // setPaymentProcessedOnContact (GET+PUT) and updateMember (GET+PUT)
            // each do a fetch-then-PUT = 6, then the job's fallback
            // getContactById does 1 more GET = 7 total. Only the LAST response
            // (the fallback getContactById) carries the BundleId.
            'api.wildapricot.org/v2.3/accounts/12345/contacts/999' => Http::sequence()
                ->push(['Id' => 999, 'Status' => 'Active',
                        'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                        'FieldValues' => []], 200)
                ->push(['Id' => 999, 'Status' => 'Active',
                        'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                        'FieldValues' => []], 200)
                ->push(['Id' => 999, 'Status' => 'Active',
                        'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                        'FieldValues' => []], 200)
                ->push(['Id' => 999, 'Status' => 'Active',
                        'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                        'FieldValues' => []], 200)
                ->push(['Id' => 999, 'Status' => 'Active',
                        'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                        'FieldValues' => []], 200)
                ->push(['Id' => 999, 'Status' => 'Active',
                        'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                        'FieldValues' => []], 200)
                ->push(['Id' => 999, 'Status' => 'Active',
                        'MembershipLevel' => ['Id' => 2, 'Name' => 'Family Membership (Primary and Spouse only)'],
                        'FieldValues' => [
                            ['FieldName' => 'Bundle ID', 'SystemCode' => 'BundleId', 'Value' => 4242],
                        ]], 200),
            'api.wildapricot.org/v2.3/accounts/12345/contacts' => Http::response(['Id' => 1001], 200),
        ]);

        $lc = \App\Models\LevelChange::create([
            'contact_id' => 999, 'member_email' => 'a@b.com',
            'from_type' => 'individual', 'to_type' => 'family',
            'amount_cents' => 4000, 'currency' => 'usd', 'status' => 'paid',
            'stripe_charge_id' => 'ch_test', 'family_members' => [],
        ]);

        (new ProcessLevelChange($lc))->handle(app(\App\Services\WildApricotService::class));

        $lc->refresh();
        $this->assertTrue($lc->processed);
        $this->assertSame(4242, $lc->wa_bundle_id);
    }
}

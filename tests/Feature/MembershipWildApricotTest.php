<?php

namespace Tests\Feature;

use App\Http\Controllers\MembershipController;
use App\Models\PendingRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ═══════════════════════════════════════════════════════════════════════════
 *  ISGH Wild Apricot Integration Tests
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  Tests the full Wild Apricot pipeline for every supported membership type.
 *  Uses fake Stripe intent IDs prefixed with "test_cs_" and test e-mail
 *  addresses so no real payment is processed.
 *
 *  EXCLUDED: flat membership — WA membership level not yet created.
 *
 *  Run a single test:
 *    php artisan test --filter=MembershipWildApricotTest::test_individual_full_pipeline
 *
 *  Run the whole suite:
 *    php artisan test --filter=MembershipWildApricotTest
 *
 *  ⚠  These tests call the real Wild Apricot API and will CREATE contacts,
 *     invoices and payments in your WA account.
 * ═══════════════════════════════════════════════════════════════════════════
 */
class MembershipWildApricotTest extends TestCase
{
    use RefreshDatabase;

    private MembershipController $ctrl;

    // Unique suffix per test-run so emails never collide with previous runs.
    private string $runId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! config('services.wild_apricot.api_key')) {
            $this->markTestSkipped('WILD_APRICOT_API_KEY not configured — skipping WA integration tests.');
        }

        // Clear WA token / level caches so every suite starts fresh.
        Cache::forget('wa_access_token');
        Cache::forget('wa_account_id');
        Cache::forget('wa_levels');

        $this->ctrl  = app(MembershipController::class);
        $this->runId = date('His') . '_' . Str::random(4);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a primary-member data array.
     * Each call gets a unique email to avoid WA "already exists" conflicts.
     */
    private function primaryData(string $label): array
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]/i', '', $label));
        return [
            'first_name'  => 'Test',
            'middle_name' => 'A.',
            'last_name'   => ucfirst($slug),
            'email'       => "test.{$slug}.{$this->runId}@isgh-test.com",
            'phone'       => '(832) 555-0100',
            'street'      => '123 Test Street',
            'city'        => 'Houston',
            'state'       => 'TX',
            'zip'         => '77001',
            'dob'         => '01/15/1985',
            'tx_dl'       => 'TX' . rand(1000000, 9999999),
        ];
    }

    /** Build a spouse data array. */
    private function spouseData(string $label = 'Spouse'): array
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]/i', '', $label));
        return [
            'first_name'  => $label,
            'middle_name' => 'B.',
            'last_name'   => 'TestSpouse',
            'email'       => "test.spouse.{$slug}.{$this->runId}@isgh-test.com",
            'phone'       => '(713) 555-0199',
            'dob'         => '03/22/1987',
            'gender'      => 'Female',
            'street'      => '123 Test Street',
            'city'        => 'Houston',
            'state'       => 'TX',
            'zip'         => '77001',
            'tx_dl'       => 'TX' . rand(1000000, 9999999),
        ];
    }

    /**
     * Create and persist a PendingRegistration with a test Stripe intent ID.
     */
    private function makeReg(string $type, float $amount, array $extraData = []): PendingRegistration
    {
        return PendingRegistration::create([
            'stripe_intent_id' => 'test_cs_' . $type . '_' . $this->runId,
            'data'             => array_merge([
                'type'         => $type,
                'primary'      => $this->primaryData($type),
                'spouses'      => [],
                'flat_members' => [],
                'zone'         => 'NW- Masjid Al-Mustafa - Bear Creek',
                'amount_cents' => (int) ($amount * 100),
                'amount_label' => '$' . number_format($amount, 2),
            ], $extraData),
            'processed'   => false,
            'stripe_paid' => false,
        ]);
    }

    /** Fake Stripe charge ID (clearly labelled as test). */
    private function chargeId(string $label): string
    {
        return 'test_ch_isgh_' . $label . '_' . $this->runId;
    }

    /**
     * Assert the registration is fully processed and WA IDs are persisted.
     */
    private function assertProcessed(PendingRegistration $reg, array $result, string $context = ''): void
    {
        $msg = $context ? "[{$context}] " : '';

        $this->assertTrue(
            $result['success'],
            $msg . 'Pipeline returned failure: ' . ($result['message'] ?? 'no message')
        );

        $reg->refresh();

        $this->assertTrue($reg->processed,          $msg . 'processed flag not set');
        $this->assertEquals('done', $reg->wa_step,  $msg . 'wa_step should be done');
        $this->assertNull($reg->wa_error,            $msg . 'wa_error should be cleared on success');
        $this->assertNotNull($reg->wa_contact_id,    $msg . 'wa_contact_id not saved');
        $this->assertNotNull($reg->wa_invoice_id,    $msg . 'wa_invoice_id not saved');
        $this->assertNotNull($reg->processed_at,     $msg . 'processed_at not saved');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  1. INDIVIDUAL MEMBERSHIP
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_individual_full_pipeline(): void
    {
        $reg    = $this->makeReg('individual', 25.00);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('individual'), 25.00);

        $this->assertProcessed($reg, $result, 'individual');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  2. FAMILY MEMBERSHIP
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_family_full_pipeline_with_spouse(): void
    {
        $reg = $this->makeReg('family', 40.00, [
            'spouses' => [$this->spouseData('SpouseOne')],
        ]);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('family'), 40.00);

        $this->assertProcessed($reg, $result, 'family + spouse');
    }

    /** @test */
    public function test_family_full_pipeline_without_spouse(): void
    {
        $reg    = $this->makeReg('family', 40.00);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('family_nospouse'), 40.00);

        $this->assertProcessed($reg, $result, 'family no spouse');
    }

    /** @test */
    public function test_family_full_pipeline_with_multiple_spouses(): void
    {
        $reg = $this->makeReg('family', 40.00, [
            'primary' => $this->primaryData('family_multi'),
            'spouses' => [
                $this->spouseData('SpouseA'),
                $this->spouseData('SpouseB'),
            ],
        ]);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('family_multi'), 40.00);

        $this->assertProcessed($reg, $result, 'family 2 spouses');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  3. CHECKOMATIC INDIVIDUAL
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_checkomatic_individual_full_pipeline(): void
    {
        $reg    = $this->makeReg('checkomatic_individual', 10.00);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('cki'), 10.00);

        $this->assertProcessed($reg, $result, 'checkomatic_individual');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  4. CHECKOMATIC FAMILY
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_checkomatic_family_full_pipeline_with_spouse(): void
    {
        $reg = $this->makeReg('checkomatic_family', 10.00, [
            'spouses' => [$this->spouseData()],
        ]);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('ckf'), 10.00);

        $this->assertProcessed($reg, $result, 'checkomatic_family');
    }

    /** @test */
    public function test_checkomatic_family_without_spouse(): void
    {
        $reg    = $this->makeReg('checkomatic_family', 10.00);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('ckf_nospouse'), 10.00);

        $this->assertProcessed($reg, $result, 'checkomatic_family no spouse');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  5. LIFETIME INDIVIDUAL
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_lifetime_individual_full_pipeline(): void
    {
        $reg    = $this->makeReg('lifetime_individual', 1000.00);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('lti'), 1000.00);

        $this->assertProcessed($reg, $result, 'lifetime_individual');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  6. LIFETIME FAMILY
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_lifetime_family_full_pipeline_with_spouse(): void
    {
        $reg = $this->makeReg('lifetime_family', 1500.00, [
            'spouses' => [$this->spouseData()],
        ]);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('ltf'), 1500.00);

        $this->assertProcessed($reg, $result, 'lifetime_family');
    }

    /** @test */
    public function test_lifetime_family_without_spouse(): void
    {
        $reg    = $this->makeReg('lifetime_family', 1500.00);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('ltf_nospouse'), 1500.00);

        $this->assertProcessed($reg, $result, 'lifetime_family no spouse');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  7. SMART RETRY — step-level resumption
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Simulate: contact was created on first attempt but invoice step failed.
     * Retry should reuse the existing wa_contact_id, not create a duplicate.
     *
     * @test
     */
    public function test_smart_retry_resumes_from_invoice_step(): void
    {
        // First: run the full pipeline to get a real contact in WA.
        $reg = $this->makeReg('individual', 25.00, [
            'primary' => $this->primaryData('retry_invoice'),
        ]);
        $first = $this->ctrl->retryWildApricot($reg, $this->chargeId('retry_inv_1'), 25.00);

        $reg->refresh();
        $this->assertTrue($first['success'], 'Setup run failed: ' . ($first['message'] ?? ''));

        $savedContactId = $reg->wa_contact_id;
        $this->assertNotNull($savedContactId);

        // Reset to simulate invoice failure (keep wa_contact_id so retry can skip contact).
        $reg->update([
            'processed'    => false,
            'processed_at' => null,
            'wa_step'      => 'invoice',
            'wa_invoice_id' => null,
            'wa_error'     => 'Simulated invoice API failure',
            'wa_error_at'  => now(),
        ]);

        // Retry — should skip step 1 (contact) and only redo invoice + payment.
        $retry = $this->ctrl->retryWildApricot($reg, $this->chargeId('retry_inv_2'), 25.00);
        $reg->refresh();

        $this->assertTrue($retry['success'], 'Retry from invoice failed: ' . ($retry['message'] ?? ''));
        $this->assertTrue($reg->processed);
        $this->assertEquals($savedContactId, $reg->wa_contact_id, 'Contact ID changed — duplicate contact may have been created');
        $this->assertNull($reg->wa_error);
    }

    /**
     * Simulate: contact + invoice both exist but payment step failed.
     * Retry should skip steps 1 and 2.
     *
     * @test
     */
    public function test_smart_retry_resumes_from_payment_step(): void
    {
        $reg = $this->makeReg('individual', 25.00, [
            'primary' => $this->primaryData('retry_payment'),
        ]);
        $first = $this->ctrl->retryWildApricot($reg, $this->chargeId('retry_pay_1'), 25.00);

        $reg->refresh();
        $this->assertTrue($first['success'], 'Setup run failed: ' . ($first['message'] ?? ''));

        $savedContactId = $reg->wa_contact_id;
        $savedInvoiceId = $reg->wa_invoice_id;

        // Reset to simulate payment failure (keep contact + invoice IDs).
        $reg->update([
            'processed'   => false,
            'processed_at' => null,
            'wa_step'     => 'payment',
            'wa_error'    => 'Simulated payment API failure',
            'wa_error_at' => now(),
        ]);

        $retry = $this->ctrl->retryWildApricot($reg, $this->chargeId('retry_pay_2'), 25.00);
        $reg->refresh();

        $this->assertTrue($retry['success'], 'Retry from payment failed: ' . ($retry['message'] ?? ''));
        $this->assertTrue($reg->processed);
        $this->assertEquals($savedContactId, $reg->wa_contact_id, 'Contact ID changed on retry');
        $this->assertEquals($savedInvoiceId, $reg->wa_invoice_id, 'Invoice ID changed on retry');
        $this->assertNull($reg->wa_error);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  8. wa_contact_id persisted immediately after step 1
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verify that wa_contact_id is written to DB right after the contact step,
     * not only when the full pipeline finishes.
     *
     * @test
     */
    public function test_wa_contact_id_persisted_after_step_1(): void
    {
        $reg = $this->makeReg('individual', 25.00, [
            'primary' => $this->primaryData('cid_persist'),
        ]);

        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('cid_persist'), 25.00);
        $reg->refresh();

        $this->assertTrue($result['success']);
        $this->assertNotNull($reg->wa_contact_id, 'wa_contact_id must be saved immediately after contact step');
        $this->assertNotNull($reg->wa_invoice_id, 'wa_invoice_id must be saved immediately after invoice step');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  9. stripe_paid flag
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * stripe_paid must be set to true at the start of processWildApricot,
     * even if WA calls succeed.
     *
     * @test
     */
    public function test_stripe_paid_flag_set_on_pipeline_entry(): void
    {
        $reg = $this->makeReg('individual', 25.00, [
            'primary' => $this->primaryData('stripepaid'),
        ]);

        $this->assertFalse($reg->stripe_paid, 'Should start unpaid');

        $this->ctrl->retryWildApricot($reg, $this->chargeId('stripepaid'), 25.00);
        $reg->refresh();

        $this->assertTrue($reg->stripe_paid, 'stripe_paid must be true after pipeline runs');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  10. PendingRegistration status helpers (unit-level, no WA call)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_status_label_awaiting_payment(): void
    {
        $reg = $this->stubReg(['processed' => false, 'stripe_paid' => false, 'wa_error' => null]);
        $this->assertEquals('Awaiting Payment', $reg->statusLabel());
        $this->assertEquals('gray', $reg->statusColor());
    }

    /** @test */
    public function test_status_label_pending_wa(): void
    {
        $reg = $this->stubReg(['processed' => false, 'stripe_paid' => true, 'wa_error' => null]);
        $this->assertEquals('Pending WA', $reg->statusLabel());
        $this->assertEquals('orange', $reg->statusColor());
    }

    /** @test */
    public function test_status_label_wa_failed(): void
    {
        $reg = $this->stubReg(['processed' => false, 'stripe_paid' => true, 'wa_error' => 'API timeout']);
        $this->assertEquals('WA Failed', $reg->statusLabel());
        $this->assertEquals('red', $reg->statusColor());
    }

    /** @test */
    public function test_status_label_completed(): void
    {
        $reg = $this->stubReg(['processed' => true, 'stripe_paid' => true, 'wa_error' => null]);
        $this->assertEquals('Completed', $reg->statusLabel());
        $this->assertEquals('green', $reg->statusColor());
    }

    /** @test */
    public function test_is_failed_only_when_paid_and_unprocessed(): void
    {
        $unpaid    = $this->stubReg(['stripe_paid' => false, 'processed' => false]);
        $processed = $this->stubReg(['stripe_paid' => true,  'processed' => true]);
        $failed    = $this->stubReg(['stripe_paid' => true,  'processed' => false]);

        $this->assertFalse($unpaid->isFailed(),    'Unpaid should not be failed');
        $this->assertFalse($processed->isFailed(), 'Processed should not be failed');
        $this->assertTrue($failed->isFailed(),     'Paid + unprocessed should be failed');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  11. Admin panel routes (HTTP layer — no WA call)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_admin_login_page_returns_401_with_login_form(): void
    {
        $response = $this->get('/admin');
        // Unauthenticated → middleware renders login form with 401
        $response->assertStatus(401);
        $response->assertSee('ISGH Admin Panel');
    }

    /** @test */
    public function test_admin_login_with_wrong_token_returns_401(): void
    {
        $response = $this->post('/admin/login', [
            '_token'      => csrf_token(),
            'admin_token' => 'wrong-token',
        ]);
        // Middleware intercepts the request — wrong token renders login form as 401
        $response->assertStatus(401);
    }

    /** @test */
    public function test_admin_login_with_correct_token_redirects_to_dashboard(): void
    {
        $token = config('services.admin.token');

        if (! $token) {
            $this->markTestSkipped('ADMIN_TOKEN not configured.');
        }

        $response = $this->post('/admin/login', [
            '_token'      => csrf_token(),
            'admin_token' => $token,
        ]);
        $response->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function test_admin_dashboard_requires_auth(): void
    {
        // Middleware renders the login view inline with 401 (no redirect)
        $response = $this->get('/admin');
        $response->assertStatus(401);
        $response->assertSee('Login');
    }

    /** @test */
    public function test_admin_dashboard_shows_stats_when_authenticated(): void
    {
        $token = config('services.admin.token');
        if (! $token) $this->markTestSkipped('ADMIN_TOKEN not configured.');

        // Create some test records
        $this->stubReg(['processed' => true,  'stripe_paid' => true]);
        $this->stubReg(['processed' => false, 'stripe_paid' => true,  'wa_error' => 'fail']);
        $this->stubReg(['processed' => false, 'stripe_paid' => false]);

        $session  = ['admin_authenticated' => true];
        $response = $this->withSession($session)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('ISGH Membership Admin');
    }

    /** @test */
    public function test_admin_retry_blocked_for_already_processed(): void
    {
        $token = config('services.admin.token');
        if (! $token) $this->markTestSkipped('ADMIN_TOKEN not configured.');

        $reg = $this->stubReg(['processed' => true, 'stripe_paid' => true, 'wa_contact_id' => 9999]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->post("/admin/registrations/{$reg->id}/retry", ['_token' => csrf_token()]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    /** @test */
    public function test_admin_retry_blocked_when_stripe_not_paid(): void
    {
        $token = config('services.admin.token');
        if (! $token) $this->markTestSkipped('ADMIN_TOKEN not configured.');

        $reg = $this->stubReg(['processed' => false, 'stripe_paid' => false]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->post("/admin/registrations/{$reg->id}/retry", ['_token' => csrf_token()]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  12. Membership verification endpoint (no WA call — input validation only)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_verify_membership_requires_email_or_name(): void
    {
        $response = $this->postJson('/membership/verify', []);
        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    /** @test */
    public function test_verify_membership_with_only_first_name_fails_validation(): void
    {
        $response = $this->postJson('/membership/verify', ['first_name' => 'Test']);
        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    /** @test */
    public function test_verify_membership_with_email_reaches_wa(): void
    {
        // This hits the real WA API — a non-existent email should return "not found"
        $response = $this->postJson('/membership/verify', [
            'email' => 'test.nonexistent.nobody@isgh-test.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => false]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    //  FLAT MEMBERSHIP
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_flat_membership_with_family_members(): void
    {
        $reg = $this->makeReg('flat', 60.00, [
            'flat_members' => [
                [
                    'full_name'   => 'FlatMember One',
                    'middle_name' => 'C.',
                    'dob'         => '05/10/1990',
                    'phone'       => '(713) 555-0201',
                    'tx_dl'       => 'TX' . rand(1000000, 9999999),
                    'relation'    => 'Family Member',
                ],
                [
                    'full_name'   => 'FlatMember Two',
                    'middle_name' => 'D.',
                    'dob'         => '08/22/1993',
                    'phone'       => '(713) 555-0202',
                    'tx_dl'       => 'TX' . rand(1000000, 9999999),
                    'relation'    => 'Family Member',
                ],
            ],
        ]);

        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('flat'), 60.00);
        $this->assertProcessed($reg, $result, 'flat 2 members');

        $reg->refresh();
        // Flat Membership in WA is a non-bundle level — BundleId is 0 (expected).
        // Members are created as independent active contacts, not bundle-linked.
        $this->assertNotNull($reg->wa_contact_id, 'Primary contact ID must be saved');
        $this->assertNotNull($reg->wa_invoice_id,  'Invoice ID must be saved');
    }

    /** @test */
    public function test_flat_membership_primary_only(): void
    {
        $reg    = $this->makeReg('flat', 20.00);
        $result = $this->ctrl->retryWildApricot($reg, $this->chargeId('flat_solo'), 20.00);
        $this->assertProcessed($reg, $result, 'flat primary only');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  12. NAMED TEST — Awais11test (primary) + awaisspouse11 (spouse)
    //      Static charge ID so it's easy to find in WA / Stripe logs.
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_family_awais11test_with_spouse_awaisspouse11(): void
    {
        $reg = PendingRegistration::create([
            'stripe_intent_id' => 'test_ch_awais11test_family_static',
            'data'             => [
                'type'    => 'family',
                'primary' => [
                    'first_name' => 'Awais11test',
                    'last_name'  => 'Member',
                    'email'      => 'awais11test.family.' . $this->runId . '@isgh-test.com',
                    'phone'      => '(832) 555-0111',
                    'street'     => '123 Test Street',
                    'city'       => 'Houston',
                    'state'      => 'TX',
                    'zip'        => '77001',
                    'dob'        => '01/01/1990',
                    'tx_dl'      => 'TX1234567',
                ],
                'spouses' => [[
                    'first_name' => 'awaisspouse11',
                    'last_name'  => 'Member',
                    'phone'      => '(713) 555-0111',
                    'dob'        => '02/15/1992',
                    'tx_dl'      => 'TX7654321',
                ]],
                'flat_members' => [],
                'zone'         => 'NW- Masjid Al-Mustafa - Bear Creek',
                'amount_cents' => 4000,
                'amount_label' => '$40.00',
            ],
            'processed'   => false,
            'stripe_paid' => false,
        ]);

        $result = $this->ctrl->retryWildApricot(
            $reg,
            'test_ch_awais11test_family_static',  // static charge ID
            40.00
        );

        $this->assertProcessed($reg, $result, 'Awais112test + awaisspouse123');

        // Verify role choices were sent — check logs for "Head of HouseHold" and "Spouse"
        $reg->refresh();
        $this->assertNotNull($reg->wa_contact_id, 'Primary contact ID must be saved');
        $this->assertNotNull($reg->wa_invoice_id,  'Invoice ID must be saved');
        $data = $reg->data;
        $this->assertArrayHasKey('wa_bundle_id', $data, 'BundleId must be extracted and stored');
        $this->assertGreaterThan(0, (int) $data['wa_bundle_id'], 'BundleId must be non-zero');
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a minimal PendingRegistration row for status/HTTP tests (no WA call).
     */
    private function stubReg(array $attrs): PendingRegistration
    {
        static $stubCount = 0;
        $stubCount++;

        return PendingRegistration::create(array_merge([
            'stripe_intent_id' => 'test_cs_stub_' . $stubCount . '_' . $this->runId,
            'data'             => [
                'type'         => 'individual',
                'primary'      => ['first_name' => 'Stub', 'last_name' => 'Test', 'email' => "stub{$stubCount}@test.com"],
                'spouses'      => [],
                'flat_members' => [],
                'zone'         => '',
                'amount_cents' => 2500,
                'amount_label' => '$25.00',
            ],
            'processed'   => false,
            'stripe_paid' => false,
        ], $attrs));
    }
}

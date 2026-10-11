<?php

namespace Tests\Feature;

use App\Mail\Membership\MembershipDisputeInternalMail;
use App\Mail\Membership\MembershipDisputeReceivedMail;
use App\Mail\Membership\MembershipInquiryInternalMail;
use App\Mail\Membership\MembershipInquiryReceivedMail;
use App\Mail\Membership\MembershipStatusNoticeMail;
use App\Support\MembershipVerification as MV;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MembershipVerificationEmailTest extends TestCase
{
    private function details(): array
    {
        return [
            'member_id' => '1001', 'name' => 'Ali Ahmad', 'type' => 'Family', 'center' => 'Not on file',
            'expiry' => '12-31-2026', 'registration' => '07-15-2026', 'email' => 'ali@example.com', 'opt_in' => 'No',
        ];
    }

    public function test_inquiry_sends_member_and_internal_emails(): void
    {
        Mail::fake();

        $this->postJson(route('membership.inquiry'), [
            'first_name' => 'Ali', 'last_name' => 'Ahmad', 'street_number' => '12345',
            'date_of_birth' => '01/02/1980', 'email' => 'ali@example.com',
        ])->assertOk()->assertJson(['success' => true]);

        Mail::assertSent(MembershipInquiryReceivedMail::class, fn ($m) => $m->hasTo('ali@example.com'));
        Mail::assertSent(MembershipInquiryInternalMail::class, fn ($m) => $m->hasTo('membership.verify@isgh.org'));
    }

    public function test_inquiry_requires_valid_email(): void
    {
        Mail::fake();

        $this->postJson(route('membership.inquiry'), [
            'first_name' => 'Ali', 'last_name' => 'Ahmad', 'street_number' => '12345',
            'date_of_birth' => '01/02/1980', 'email' => 'nope',
        ])->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_dispute_requires_a_prior_verification(): void
    {
        Mail::fake();

        $this->postJson(route('membership.dispute'), ['message' => 'x'])->assertStatus(422);

        Mail::assertNothingSent();
    }

    private function uploadPayload(array $overrides = []): array
    {
        return array_merge([
            'contact_id' => 1001,
            'photo'      => \Illuminate\Http\UploadedFile::fake()->image('id.jpg'),
            'email'      => 'ali@example.com',
            'consent'    => '1',
        ], $overrides);
    }

    public function test_upload_requires_consent(): void
    {
        $this->withSession(['membership_verified' => ['contact_id' => 1001, 'scenario' => MV::ACTIVE_ELIGIBLE]])
            ->postJson(route('membership.upload-photo'), $this->uploadPayload(['consent' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('consent');
    }

    public function test_upload_rejected_without_a_verified_session(): void
    {
        $this->postJson(route('membership.upload-photo'), $this->uploadPayload())->assertStatus(403);
    }

    public function test_upload_rejected_for_ineligible_scenarios(): void
    {
        foreach ([MV::ACTIVE_LATE, MV::LAPSED, MV::OTHER] as $scenario) {
            $this->withSession(['membership_verified' => ['contact_id' => 1001, 'scenario' => $scenario]])
                ->postJson(route('membership.upload-photo'), $this->uploadPayload())
                ->assertStatus(403);
        }
    }

    public function test_upload_rejected_for_a_different_contact_than_verified(): void
    {
        $this->withSession(['membership_verified' => ['contact_id' => 2002, 'scenario' => MV::ACTIVE_ELIGIBLE]])
            ->postJson(route('membership.upload-photo'), $this->uploadPayload())
            ->assertStatus(403);
    }

    public function test_status_notice_renders_each_scenario(): void
    {
        foreach ([MV::ACTIVE_ELIGIBLE, MV::ACTIVE_LATE, MV::LAPSED] as $scenario) {
            $mail = new MembershipStatusNoticeMail($scenario, $this->details());
            $html = $mail->render();

            $this->assertStringContainsString('Assalamu Alaikum Ali Ahmad', $html);
            $this->assertStringContainsString('Jazakallah Khair', $html);
            $this->assertStringNotContainsStringIgnoringCase(' but ', $html);
        }

        $this->assertStringContainsString('Not eligible', (new MembershipStatusNoticeMail(MV::ACTIVE_LATE, $this->details()))->render());
        $this->assertStringContainsString('Eligible', (new MembershipStatusNoticeMail(MV::ACTIVE_ELIGIBLE, $this->details()))->render());
    }

    public function test_dispute_and_inquiry_mails_render(): void
    {
        $d = $this->details();

        $internal = new MembershipDisputeInternalMail(MV::LAPSED, $d, 'I renewed in May', '07-01-2026 10:00 AM UTC');
        $this->assertStringContainsString('I renewed in May', $internal->render());
        $this->assertStringContainsString('Member ID 1001', $internal->envelope()->subject);

        $this->assertStringContainsString('proof of renewal', (new MembershipDisputeReceivedMail(MV::LAPSED, 'Ali Ahmad'))->render());
        $this->assertStringNotContainsString('proof of renewal', (new MembershipDisputeReceivedMail(MV::ACTIVE_LATE, 'Ali Ahmad'))->render());

        $inq = new MembershipInquiryInternalMail('Ali', 'Ahmad', '12345', '01/02/1980', 'ali@example.com', 'now');
        $this->assertStringContainsString('01/02/1980', $inq->render());
    }
}

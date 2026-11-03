<?php

namespace Tests\Feature;

use App\Models\MemberOptIn;
use App\Services\WildApricotService;
use App\Support\MembershipVerification as MV;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

class MemberOptInUploadTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedSession(): array
    {
        return ['membership_verified' => [
            'contact_id' => 1001, 'scenario' => MV::ACTIVE_ELIGIBLE,
            'first_name' => 'Ali', 'last_name' => 'Ahmad', 'type' => 'Family', 'center' => 'Main Center',
        ]];
    }

    private function payload(string $email = 'Ali@Example.com'): array
    {
        return [
            'contact_id' => 1001,
            'photo'      => UploadedFile::fake()->image('id.jpg'),
            'email'      => $email,
            'consent'    => '1',
        ];
    }

    private function mockWa(bool $uploadFails = false): MockInterface
    {
        return $this->mock(WildApricotService::class, function (MockInterface $m) use ($uploadFails) {
            $upload = $m->shouldReceive('uploadContactPicture');
            $uploadFails ? $upload->andThrow(new \RuntimeException('WA down')) : $upload->once();
            $m->shouldReceive('addGroupParticipation')->zeroOrMoreTimes();
            $m->shouldReceive('getAccountId')->andReturn(1);
            $m->shouldReceive('apiGetPublic')->andReturn(
                new Response(new PsrResponse(200, [], json_encode(['FirstName' => 'Ali', 'Email' => 'ali@example.com'])))
            );
            $m->shouldReceive('getFieldSystemCodePublic')->andReturn(null);
        });
    }

    public function test_upload_stores_the_opt_in_record(): void
    {
        Mail::fake();
        $this->mockWa();

        $this->withSession($this->verifiedSession())
            ->postJson(route('membership.upload-photo'), $this->payload())
            ->assertOk()->assertJson(['success' => true]);

        $row = MemberOptIn::first();
        $this->assertSame('ali@example.com', $row->member_email);
        $this->assertSame(1001, $row->contact_id);
        $this->assertSame('Ali', $row->first_name);
        $this->assertSame('Family', $row->membership_type);
        $this->assertSame('Main Center', $row->center);
        $this->assertTrue($row->consent_given);
        $this->assertSame(MemberOptIn::STATUS_WAITING, $row->status);
    }

    public function test_upload_is_rejected_when_the_email_was_already_used(): void
    {
        MemberOptIn::create(['contact_id' => 5, 'member_email' => 'ali@example.com']);
        $this->mock(WildApricotService::class, fn (MockInterface $m) => $m->shouldNotReceive('uploadContactPicture'));

        $this->withSession($this->verifiedSession())
            ->postJson(route('membership.upload-photo'), $this->payload('ALI@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', fn ($m) => str_contains($m, 'already been used for opt-in'));

        $this->assertSame(1, MemberOptIn::count());
    }

    public function test_opt_in_record_is_removed_when_the_wa_upload_fails(): void
    {
        $this->mockWa(uploadFails: true);

        $this->withSession($this->verifiedSession())
            ->postJson(route('membership.upload-photo'), $this->payload())
            ->assertStatus(500);

        $this->assertSame(0, MemberOptIn::count());
    }
}

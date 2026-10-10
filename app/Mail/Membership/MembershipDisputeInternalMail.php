<?php

namespace App\Mail\Membership;

use App\Support\MembershipVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to membership.verify when a member disputes a result. */
class MembershipDisputeInternalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $scenario,
        public array $d,
        public string $memberMessage,
        public string $submittedAt,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->scenario === MembershipVerification::LAPSED
            ? 'Lapsed Result Disputed'
            : 'Eligibility Disputed';

        return new Envelope(
            subject: "Verify Membership Status: {$label}: {$this->d['name']}, Member ID {$this->d['member_id']}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.membership.dispute-internal');
    }
}

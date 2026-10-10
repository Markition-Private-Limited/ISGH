<?php

namespace App\Mail\Membership;

use App\Support\MembershipVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the member after a lookup (scenarios 1, 3 and 4). */
class MembershipStatusNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $scenario, public array $details) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->scenario) {
            MembershipVerification::ACTIVE_LATE     => 'ISGH Membership Verification: Active Membership and 2026 Voting Eligibility',
            MembershipVerification::LAPSED          => 'ISGH Membership Verification: Lapsed Status and 2026 Voting Eligibility',
            default                                 => 'ISGH Membership Verification: Active and Eligible to Opt In for Online Voting',
        });
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership.status-notice',
            with: [
                'verificationLink' => route('membership-verification'),
                'renewalLink'      => MembershipVerification::renewalLink(),
            ],
        );
    }
}

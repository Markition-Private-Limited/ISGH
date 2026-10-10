<?php

namespace App\Mail\Membership;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the member after they dispute a result. */
class MembershipDisputeReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $scenario, public string $name) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'ISGH Membership Verification Request Received: Under Review');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.membership.dispute-received');
    }
}

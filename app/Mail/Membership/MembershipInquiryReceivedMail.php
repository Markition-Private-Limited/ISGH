<?php

namespace App\Mail\Membership;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the person after a record-not-found inquiry. */
class MembershipInquiryReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $streetNumber,
        public string $dob,
        public string $email,
        public string $submittedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'ISGH Membership Inquiry Received: Under Review');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.membership.inquiry-received');
    }
}

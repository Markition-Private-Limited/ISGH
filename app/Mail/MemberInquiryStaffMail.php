<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberInquiryStaffMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Membership Inquiry – ' . $this->data['first_name'] . ' ' . $this->data['last_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.member-inquiry-staff',
        );
    }
}

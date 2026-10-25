<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccountActivationMail extends Mailable
{
    public function __construct(
        public string $name,
        public string $activationUrl,
        public int $ttlMinutes = 60,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Aktivasi Akun TKJ Digital Hub');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activation',
            with: [
                'name' => $this->name,
                'activationUrl' => $this->activationUrl,
                'ttlMinutes' => $this->ttlMinutes,
            ],
        );
    }
}

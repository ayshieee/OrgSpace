<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $creator,
        public string $memberEmail,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've Been Added to an Organization on OrgSpace",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.members.account-created',
            with: [
                'organizationName' => $this->organization->name,
                'creatorName' => $this->creator->name,
                'memberEmail' => $this->memberEmail,
                'password' => $this->password,
            ],
        );
    }
}

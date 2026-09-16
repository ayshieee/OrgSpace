<?php

namespace App\Mail;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Announcement $announcement,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: \"{$this->announcement->title}\" — {$this->announcement->organization->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.announcements.reminder',
            with: [
                'recipientName' => $this->recipientName,
                'organizationName' => $this->announcement->organization->name,
                'title' => $this->announcement->title,
                'body' => $this->announcement->body,
                'authorName' => $this->announcement->author->name,
                'url' => route('organizations.announcements.index', $this->announcement->organization_id),
            ],
        );
    }
}

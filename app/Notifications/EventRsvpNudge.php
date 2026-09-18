<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventRsvpNudge extends Notification
{
    use Queueable;

    public function __construct(protected Event $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'RSVP needed',
            'body' => "Please confirm your attendance for \"{$this->event->title}\".",
            'link' => route('organizations.events.show', [$this->event->organization_id, $this->event->id]),
        ];
    }
}

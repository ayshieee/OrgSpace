<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventCallSheetBroadcast extends Notification
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
        $when = $this->event->starts_at->format('D, M j, Y \a\t g:i A');
        $callTime = $this->event->call_time
            ? ' — call time '.\Carbon\Carbon::parse($this->event->call_time)->format('g:i A')
            : '';

        return [
            'title' => 'Call sheet: '.$this->event->title,
            'body' => "{$when}{$callTime}".($this->event->location ? " at {$this->event->location}" : ''),
            'link' => route('organizations.events.show', [$this->event->organization_id, $this->event->id]),
        ];
    }
}

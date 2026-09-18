<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JoinRequestDenied extends Notification
{
    use Queueable;

    public function __construct(protected Organization $organization)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Join request denied',
            'body' => "Your request to join {$this->organization->name} was not approved.",
            'link' => route('dashboard'),
        ];
    }
}

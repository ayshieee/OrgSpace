<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JoinRequestApproved extends Notification
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
            'title' => 'Join request approved',
            'body' => "You're in — you now have access to {$this->organization->name}.",
            'link' => route('organizations.dashboard', $this->organization->id),
        ];
    }
}

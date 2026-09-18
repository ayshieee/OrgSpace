<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementReaction extends Notification
{
    use Queueable;

    public function __construct(protected Announcement $announcement, protected User $reactor)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "{$this->reactor->name} reacted to your announcement",
            'body' => $this->announcement->title,
            'link' => route('organizations.announcements.index', $this->announcement->organization_id),
        ];
    }
}

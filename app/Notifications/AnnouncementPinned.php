<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementPinned extends Notification
{
    use Queueable;

    public function __construct(protected Announcement $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Pinned announcement',
            'body' => $this->announcement->title,
            'link' => route('organizations.announcements.index', $this->announcement->organization_id),
        ];
    }
}

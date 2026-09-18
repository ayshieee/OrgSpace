<?php

namespace App\Notifications;

use App\Models\AnnouncementComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CommentPosted extends Notification
{
    use Queueable;

    public function __construct(protected AnnouncementComment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New comment on your announcement',
            'body' => Str::limit($this->comment->body, 100),
            'link' => route('organizations.announcements.index', $this->comment->announcement->organization_id),
        ];
    }
}

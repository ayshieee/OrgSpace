<?php

namespace App\Notifications;

use App\Models\AnnouncementComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CommentReplied extends Notification
{
    use Queueable;

    public function __construct(protected AnnouncementComment $reply)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New reply to your comment',
            'body' => Str::limit($this->reply->body, 100),
            'link' => route('organizations.announcements.index', $this->reply->announcement->organization_id),
        ];
    }
}

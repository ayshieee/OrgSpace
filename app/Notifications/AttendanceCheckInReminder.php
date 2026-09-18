<?php

namespace App\Notifications;

use App\Models\AttendanceSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceCheckInReminder extends Notification
{
    use Queueable;

    public function __construct(protected AttendanceSession $session)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Check in now',
            'body' => "\"{$this->session->title}\" is live and you haven't checked in yet.",
            'link' => route('organizations.attendance.show', [$this->session->organization_id, $this->session->id]),
        ];
    }
}

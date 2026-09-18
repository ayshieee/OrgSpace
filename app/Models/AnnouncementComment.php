<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AnnouncementComment extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(AnnouncementComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(AnnouncementComment::class, 'parent_id')->oldest();
    }

    public function attachments()
    {
        return $this->hasMany(AnnouncementCommentAttachment::class);
    }
}

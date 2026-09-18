<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AnnouncementCommentAttachment extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function comment()
    {
        return $this->belongsTo(AnnouncementComment::class, 'announcement_comment_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

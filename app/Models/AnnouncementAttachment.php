<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AnnouncementAttachment extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

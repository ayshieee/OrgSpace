<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_important' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function attachments()
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    /**
     * Top-level comments only, for display — creating a comment (including a
     * reply) goes through allComments() instead, since this relation's
     * whereNull scope would otherwise fight an explicit parent_id.
     */
    public function comments()
    {
        return $this->hasMany(AnnouncementComment::class)->whereNull('parent_id')->oldest();
    }

    public function allComments()
    {
        return $this->hasMany(AnnouncementComment::class);
    }

    public function reactions()
    {
        return $this->hasMany(AnnouncementReaction::class);
    }
}

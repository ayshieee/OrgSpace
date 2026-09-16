<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    // This automatically converts the JSON database columns into PHP arrays
    protected $casts = [
        'settings' => 'array',
        'branding' => 'array',
        'activated_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    // An organization has many members
    public function members()
    {
        return $this->hasMany(OrganizationMember::class);
    }

    // An organization has many roles
    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    // An organization has many enabled/disabled feature modules
    public function features()
    {
        return $this->hasMany(OrgFeature::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function joinRequests()
    {
        return $this->hasMany(OrganizationJoinRequest::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function files()
    {
        return $this->hasMany(OrgFile::class);
    }

    public function isPublic(): bool
    {
        return $this->is_public === true;
    }

    /**
     * Generates a short, unique, human-transcribable join code. Excludes
     * 0/O/1/I/L to avoid ambiguity when an officer reads it aloud or a
     * member types it in by hand.
     */
    public static function generateUniqueJoinCode(): string
    {
        $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 8))
                ->map(fn () => $charset[random_int(0, strlen($charset) - 1)])
                ->implode('');
        } while (self::where('join_code', $code)->exists());

        return $code;
    }
}
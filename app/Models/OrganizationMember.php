<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrganizationMember extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrganizationMember $member) {
            if (! $member->qr_token) {
                $member->qr_token = Str::random(32);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // A member can have many roles (e.g., President AND Section Leader)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'organization_member_role');
    }

    public function hasPermission(string $key): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->hasPermission($key));
    }

    public function favoritedMusic()
    {
        return $this->belongsToMany(MusicEntry::class, 'music_favorites');
    }
}
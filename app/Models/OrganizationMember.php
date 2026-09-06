<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganizationMember extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

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
}
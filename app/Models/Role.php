<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Replace this role's permission set with the given list of keys.
     */
    public function syncPermissions(array $keys): void
    {
        $this->permissions()->delete();

        foreach (array_unique($keys) as $key) {
            $this->permissions()->create(['permission' => $key]);
        }
    }

    /**
     * A system role implicitly holds every permission and is never gated by
     * the role_permissions table. No role is created this way currently —
     * this exists for any future need to grant unconditional full access.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->is_system) {
            return true;
        }

        return $this->permissions()->where('permission', $key)->exists();
    }
}
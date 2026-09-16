<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}

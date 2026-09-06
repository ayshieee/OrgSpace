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
    ];

    // An organization has many members
    public function members()
    {
        return $this->hasMany(OrganizationMember::class);
    }
}
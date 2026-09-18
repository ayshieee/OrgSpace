<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MusicFavorite extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function musicEntry()
    {
        return $this->belongsTo(MusicEntry::class);
    }

    public function organizationMember()
    {
        return $this->belongsTo(OrganizationMember::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MusicEntry extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'annotations' => 'array',
        'is_restricted' => 'boolean',
        'is_watermarked' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(OrganizationMember::class, 'music_favorites');
    }
}

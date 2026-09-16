<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrgFile extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'size' => 'integer',
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
}

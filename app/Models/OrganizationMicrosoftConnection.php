<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganizationMicrosoftConnection extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function connectedBy()
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }
}

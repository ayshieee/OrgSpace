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
        'is_folder' => 'boolean',
        'ms_copy_uploaded_at' => 'datetime',
        'ms_last_pulled_at' => 'datetime',
    ];

    public const PREVIEW_NATIVE = 'native';

    public const PREVIEW_CONVERTED = 'converted';

    public const PREVIEW_FAILED = 'failed';

    public const PREVIEW_UNSUPPORTED = 'unsupported';

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function parent()
    {
        return $this->belongsTo(OrgFile::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrgFile::class, 'parent_id');
    }

    /**
     * Resolves the current member's access tier for this item, or null if
     * they cannot see it at all. manage_files is always the ceiling — it can
     * never be delegated away or restricted per file, so the Adviser/anyone
     * holding it can never be locked out of their own org's files.
     */
    public function tierFor(OrganizationMember $membership): ?string
    {
        if ($membership->hasPermission('manage_files')) {
            return 'manage';
        }

        if ($this->edit_permission !== null && $membership->hasPermission($this->edit_permission)) {
            return 'edit';
        }

        if ($this->view_permission === null || $membership->hasPermission($this->view_permission)) {
            return 'view';
        }

        return null;
    }

    public function canBeViewedBy(OrganizationMember $membership): bool
    {
        return $this->tierFor($membership) !== null;
    }

    public function canBeEditedBy(OrganizationMember $membership): bool
    {
        return in_array($this->tierFor($membership), ['edit', 'manage'], true);
    }
}

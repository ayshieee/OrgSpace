<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'session_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * Creates a session and immediately seeds one absent AttendanceRecord for
     * every active member. Shared by AttendanceController::store() and
     * EventsController's auto-session creation so the "a session always has
     * a full roster the moment it exists" invariant lives in one place.
     * $attributes must already be validated/authorized by the caller.
     */
    public static function createWithRoster(Organization $organization, array $attributes): self
    {
        $session = $organization->attendanceSessions()->create($attributes);

        $memberIds = $organization->members()->where('is_active', true)->pluck('id');

        $session->records()->createMany(
            $memberIds->map(fn ($id) => ['organization_member_id' => $id, 'status' => 'absent'])->all()
        );

        return $session;
    }

    /**
     * The session's real lifecycle state — the single source of truth used
     * both for display (Index/Show) and for enforcement (check-in gate,
     * officer mutation guards). A session with no start/end time behaves
     * exactly as before: "active" for its whole session_date, until closed.
     */
    public function status(): string
    {
        if ($this->isClosed()) {
            return 'ended';
        }

        $date = $this->session_date->toDateString();
        $today = now()->toDateString();

        if ($date > $today) {
            return 'scheduled';
        }

        if ($this->start_time && now()->lt(Carbon::parse("{$date} {$this->start_time}"))) {
            return 'scheduled';
        }

        if ($this->end_time) {
            return now()->gt(Carbon::parse("{$date} {$this->end_time}")) ? 'expired' : 'active';
        }

        // No end_time set: fall back to the original rule — a session is
        // only ever "active" on its own session_date.
        return $date === $today ? 'active' : 'expired';
    }
}

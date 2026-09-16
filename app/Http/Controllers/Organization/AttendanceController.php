<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    use ChecksOrganizationPermission;

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);

        $sessions = $organization->attendanceSessions()
            ->withCount([
                'records as present_count' => fn ($q) => $q->where('status', 'present'),
                'records as excused_count' => fn ($q) => $q->where('status', 'excused'),
                'records as absent_count' => fn ($q) => $q->where('status', 'absent'),
            ])
            ->orderByDesc('session_date')
            ->get()
            ->map(fn (AttendanceSession $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'session_date' => $s->session_date->format('M j, Y'),
                'is_closed' => $s->isClosed(),
                'present_count' => $s->present_count,
                'excused_count' => $s->excused_count,
                'absent_count' => $s->absent_count,
            ]);

        return Inertia::render('Organizations/Attendance/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'sessions' => $sessions,
            'events' => $organization->events()->orderByDesc('starts_at')->get(['id', 'title']),
            'canManage' => $membership->hasPermission('manage_attendance'),
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date'],
            'event_id' => ['nullable', 'string'],
        ]);

        $session = $organization->attendanceSessions()->create([
            ...$validated,
            'event_id' => $validated['event_id'] ?: null,
            'created_by' => $request->user()->id,
        ]);

        $memberIds = $organization->members()->where('is_active', true)->pluck('id');

        $session->records()->createMany(
            $memberIds->map(fn ($id) => ['organization_member_id' => $id, 'status' => 'absent'])->all()
        );

        return redirect()->route('organizations.attendance.show', [$organization->id, $session->id]);
    }

    public function show(Request $request, Organization $organization, AttendanceSession $session): Response
    {
        $membership = $this->membership($request, $organization);

        abort_unless($session->organization_id === $organization->id, 404);

        $records = $session->records()
            ->with('member.user')
            ->get()
            ->sortBy(fn ($r) => $r->member->user->name)
            ->map(fn (AttendanceRecord $r) => [
                'id' => $r->id,
                'member_name' => $r->member->user->name,
                'status' => $r->status,
                'marked_at' => $r->marked_at?->format('g:i A'),
                'self_checked_in' => $r->marked_by === $r->member->user_id,
            ])
            ->values();

        $presentCount = $records->where('status', 'present')->count();

        return Inertia::render('Organizations/Attendance/Show', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'session_date' => $session->session_date->format('M j, Y'),
                'is_closed' => $session->isClosed(),
            ],
            'records' => $records,
            'presentCount' => $presentCount,
            'checkInUrl' => route('attendance.check-in', $session->id),
            'canManage' => $membership->hasPermission('manage_attendance'),
        ]);
    }

    public function updateRecord(Request $request, Organization $organization, AttendanceSession $session, AttendanceRecord $record): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        abort_unless($session->organization_id === $organization->id && $record->attendance_session_id === $session->id, 404);
        abort_if($session->isClosed(), 422, 'This session is closed.');

        $validated = $request->validate([
            'status' => ['required', 'in:present,excused,absent'],
        ]);

        $record->update([
            'status' => $validated['status'],
            'marked_at' => now(),
            'marked_by' => $request->user()->id,
        ]);

        return back();
    }

    public function close(Request $request, Organization $organization, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        abort_unless($session->organization_id === $organization->id, 404);

        $session->update(['closed_at' => now()]);

        return back()->with('success', 'Attendance session closed.');
    }

    public function destroy(Request $request, Organization $organization, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        abort_unless($session->organization_id === $organization->id, 404);

        $session->delete();

        return redirect()->route('organizations.attendance.index', $organization->id)->with('success', 'Session deleted.');
    }
}

<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Concerns\ComputesAttendanceEligibility;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Notifications\AttendanceCheckInReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    use ChecksOrganizationPermission, ComputesAttendanceEligibility;

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_attendance');
        $canViewReports = $membership->hasPermission('view_reports');

        $sessions = $organization->attendanceSessions()
            ->withCount([
                'records as present_count' => fn ($q) => $q->where('status', 'present'),
                'records as excused_count' => fn ($q) => $q->where('status', 'excused'),
                'records as absent_count' => fn ($q) => $q->where('status', 'absent'),
            ])
            ->orderByDesc('session_date')
            ->get()
            ->map(function (AttendanceSession $s) {
                $status = $s->status();

                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'session_date' => $s->session_date->format('M j, Y'),
                    'status' => $status,
                    'is_closed' => $s->isClosed(),
                    'is_live' => $status === 'active',
                    'present_count' => $s->present_count,
                    'excused_count' => $s->excused_count,
                    'absent_count' => $s->absent_count,
                ];
            });

        $pendingExcuseCount = AttendanceRecord::query()
            ->whereIn('attendance_session_id', $organization->attendanceSessions()->pluck('id'))
            ->where('excuse_status', 'pending')
            ->count();

        return Inertia::render('Organizations/Attendance/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'sessions' => $sessions,
            'events' => $organization->events()->orderByDesc('starts_at')->get(['id', 'title']),
            'canManage' => $canManage,
            'canViewReports' => $canViewReports,
            'myQrToken' => $membership->qr_token,
            'kpis' => [
                'average_attendance' => $this->computeAttendanceIndex($organization),
                'active_live_sessions' => $sessions->where('is_live', true)->count(),
                'pending_excuse_count' => $pendingExcuseCount,
            ],
            'eligibility' => $canViewReports ? $this->computeEligibility($organization) : null,
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'session_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'event_id' => ['nullable', 'string'],
        ]);

        $session = AttendanceSession::createWithRoster($organization, [
            ...$validated,
            'description' => $validated['description'] ?: null,
            'start_time' => $validated['start_time'] ?: null,
            'end_time' => $validated['end_time'] ?: null,
            'event_id' => $validated['event_id'] ?: null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('organizations.attendance.show', [$organization->id, $session->id]);
    }

    public function show(Request $request, Organization $organization, AttendanceSession $session): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_attendance');

        abort_unless($session->organization_id === $organization->id, 404);

        $records = $session->records()
            ->with(['member.user', 'excuseReviewer'])
            ->get()
            ->sortBy(fn ($r) => $r->member->user->name)
            ->map(fn (AttendanceRecord $r) => [
                'id' => $r->id,
                'member_id' => $r->organization_member_id,
                'user_id' => $r->member->user_id,
                'member_name' => $r->member->user->name,
                'member_email' => $r->member->user->email,
                'avatar_path' => $r->member->user->avatar_path,
                'membership_number' => $r->member->membership_number,
                'section' => $r->member->section,
                'status' => $r->status,
                'marked_at' => $r->marked_at?->format('g:i A'),
                'self_checked_in' => $r->marked_by === $r->member->user_id,
                'excuse_note' => $r->excuse_note,
                'excuse_submitted_at' => $r->excuse_submitted_at?->diffForHumans(),
                'excuse_status' => $r->excuse_status,
                'excuse_reviewed_by_name' => $r->excuseReviewer?->name,
                'excuse_reviewed_at' => $r->excuse_reviewed_at?->format('M j, g:i A'),
                'is_mine' => $r->member->user_id === $request->user()->id,
            ])
            ->values();

        $presentCount = $records->where('status', 'present')->count();

        $sectionBreakdown = $records
            ->filter(fn ($r) => ! empty($r['section']))
            ->groupBy('section')
            ->map(fn ($group, $section) => [
                'section' => $section,
                'present' => $group->where('status', 'present')->count(),
                'total' => $group->count(),
            ])
            ->values();

        $status = $session->status();

        return Inertia::render('Organizations/Attendance/Show', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'description' => $session->description,
                'session_date' => $session->session_date->format('M j, Y'),
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'status' => $status,
                'is_closed' => $session->isClosed(),
                'is_live' => $status === 'active',
                'started_at' => $session->created_at->toIso8601String(),
            ],
            'records' => $records,
            'presentCount' => $presentCount,
            'sectionBreakdown' => $sectionBreakdown,
            'checkInUrl' => route('attendance.check-in', $session->id),
            'canManage' => $canManage,
        ]);
    }

    public function updateRecord(Request $request, Organization $organization, AttendanceSession $session, AttendanceRecord $record): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        abort_unless($session->organization_id === $organization->id && $record->attendance_session_id === $session->id, 404);
        $this->abortIfSessionUnavailable($session);

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

    /**
     * Self-service: a member explains an absence/late mark on their own
     * record. No manage_attendance gate — only requires the record is
     * theirs.
     */
    public function submitExcuse(Request $request, Organization $organization, AttendanceSession $session, AttendanceRecord $record): RedirectResponse
    {
        $this->membership($request, $organization);

        abort_unless($session->organization_id === $organization->id && $record->attendance_session_id === $session->id, 404);
        abort_unless($record->member->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $record->update([
            'excuse_note' => $validated['note'],
            'excuse_submitted_at' => now(),
            'excuse_status' => 'pending',
        ]);

        return back()->with('success', 'Your excuse was submitted for review.');
    }

    /**
     * Officer decision on a submitted excuse: approve as excused, convert
     * to a full present (no penalty), or deny (attendance status is left
     * as-is). Each record keeps its own reviewer/timestamp — the audit
     * trail lives on the record itself, no separate log table needed.
     */
    public function reviewExcuse(Request $request, Organization $organization, AttendanceSession $session, AttendanceRecord $record): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        abort_unless($session->organization_id === $organization->id && $record->attendance_session_id === $session->id, 404);

        $validated = $request->validate([
            'decision' => ['required', 'in:approve_excused,convert_present,deny'],
        ]);

        $statusMap = [
            'approve_excused' => 'excused',
            'convert_present' => 'present',
            'deny' => $record->status,
        ];

        $record->update([
            'status' => $statusMap[$validated['decision']],
            'excuse_status' => $validated['decision'] === 'deny' ? 'denied' : 'approved',
            'excuse_reviewed_by' => $request->user()->id,
            'excuse_reviewed_at' => now(),
            'marked_at' => $validated['decision'] !== 'deny' ? now() : $record->marked_at,
            'marked_by' => $validated['decision'] !== 'deny' ? $request->user()->id : $record->marked_by,
        ]);

        return back()->with('success', 'Excuse reviewed.');
    }

    /**
     * Sends a real in-app notification (the same notification center built
     * earlier this session) to every member on a live session who hasn't
     * checked in yet — a genuine substitute for the mockup's "broadcast",
     * using infrastructure that actually exists.
     */
    public function broadcastReminder(Request $request, Organization $organization, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        abort_unless($session->organization_id === $organization->id, 404);
        $this->abortIfSessionUnavailable($session);

        $notPresent = $session->records()
            ->where('status', '!=', 'present')
            ->with('member.user')
            ->get();

        foreach ($notPresent as $record) {
            $record->member->user->notify(new AttendanceCheckInReminder($session));
        }

        $count = $notPresent->count();

        return back()->with('success', $count > 0
            ? "Reminder sent to {$count} member".($count === 1 ? '' : 's').'.'
            : 'Everyone has already checked in.');
    }

    /**
     * Officer-side camera scan of a member's personal QR badge — a second,
     * verified-roll-call check-in path alongside the existing QR-link
     * self-check-in (member scans the *session's* QR, no officer involved).
     * Here the officer scans the *member's* QR, so this is gated on
     * manage_attendance like every other mutation on this controller.
     */
    public function scan(Request $request, Organization $organization, AttendanceSession $session): JsonResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_attendance');

        abort_unless($session->organization_id === $organization->id, 404);
        $this->abortIfSessionUnavailable($session);

        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('qr_token', $validated['token'])
            ->where('is_active', true)
            ->first();

        if (! $member) {
            return response()->json(['message' => 'QR code not recognized for this organization.'], 404);
        }

        $record = $session->records()->firstOrCreate(
            ['organization_member_id' => $member->id],
            ['status' => 'absent']
        );

        $alreadyPresent = $record->status === 'present';

        $record->update([
            'status' => 'present',
            'marked_at' => now(),
            'marked_by' => $request->user()->id,
        ]);

        return response()->json([
            'member_name' => $member->user->name,
            'already_present' => $alreadyPresent,
            'marked_at' => $record->marked_at->format('g:i A'),
        ]);
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

    /**
     * Blocks officer mutations (mark, scan, broadcast) once a session is no
     * longer accepting attendance — either manually closed or past its own
     * end_time. Mirrors the same lifecycle gate AttendanceCheckInController
     * enforces for member self-check-in.
     */
    protected function abortIfSessionUnavailable(AttendanceSession $session): void
    {
        abort_if(in_array($session->status(), ['ended', 'expired'], true), 422, 'This session is no longer accepting attendance.');
    }
}

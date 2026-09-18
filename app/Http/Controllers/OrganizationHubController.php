<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesAttendanceEligibility;
use App\Models\AttendanceRecord;
use App\Models\Organization;
use App\Models\OrganizationJoinRequest;
use App\Models\OrganizationMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationHubController extends Controller
{
    use ComputesAttendanceEligibility;

    /**
     * "My Organizations" — the landing page after login. Groups every
     * organization the user is connected to into Manage / Member of /
     * Pending Requests / Hidden & Archived.
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasAnyOrganization()) {
            return redirect()->route('get-started.show');
        }

        $draftMembership = $user->organizationMemberships()
            ->whereHas('organization', fn ($q) => $q->where('status', 'draft'))
            ->with('organization')
            ->first();

        if ($draftMembership) {
            $organization = $draftMembership->organization;

            return redirect()->route("onboarding.{$organization->onboarding_step}.show");
        }

        $activeMemberships = $user->organizationMemberships()
            ->where('is_active', true)
            ->whereHas('organization', fn ($q) => $q->where('status', 'active'))
            ->with(['organization', 'roles'])
            ->get();

        [$manage, $memberOf] = $activeMemberships->partition(
            fn (OrganizationMember $m) => $m->hasPermission('manage_roster') || $m->hasPermission('manage_org_settings')
        );

        $pendingRequests = OrganizationJoinRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('organization')
            ->get()
            ->map(fn ($request) => [
                'id' => $request->organization->id,
                'name' => $request->organization->name,
                'type' => $request->organization->type,
                'logo_path' => $request->organization->logo_path,
            ]);

        $leftMemberships = $user->organizationMemberships()
            ->where('is_active', false)
            ->whereHas('organization', fn ($q) => $q->where('status', 'active'))
            ->with('organization')
            ->get()
            ->map(fn (OrganizationMember $m) => [
                'id' => $m->organization->id,
                'name' => $m->organization->name,
                'type' => $m->organization->type,
                'logo_path' => $m->organization->logo_path,
                'note' => 'Left '.$m->updated_at->format('F Y'),
            ]);

        $archivedMemberships = $user->organizationMemberships()
            ->whereHas('organization', fn ($q) => $q->withTrashed()->whereNotNull('deleted_at'))
            ->with(['organization' => fn ($q) => $q->withTrashed()])
            ->get()
            ->map(fn (OrganizationMember $m) => [
                'id' => $m->organization->id,
                'name' => $m->organization->name,
                'type' => $m->organization->type,
                'logo_path' => $m->organization->logo_path,
                'note' => 'Archived Org',
            ]);

        return Inertia::render('Organizations/Hub', [
            'manage' => $manage->values()->map(fn ($m) => $this->formatMembershipCard($m)),
            'memberOf' => $memberOf->values()->map(fn ($m) => $this->formatMembershipCard($m)),
            'pendingRequests' => $pendingRequests->values(),
            'hidden' => $leftMemberships->concat($archivedMemberships)->values(),
        ]);
    }

    /**
     * A single organization's dashboard, reached by clicking into it from
     * the hub. There's no dedicated audit-log table — the "activity feed" is
     * assembled from real timestamps across membership, join requests,
     * announcements, events, and closed attendance sessions.
     */
    public function show(Request $request, Organization $organization): Response
    {
        $user = $request->user();

        $membership = $organization->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('roles.permissions')
            ->first();

        abort_unless($membership, 403);

        $canReview = $membership->hasPermission('manage_roster');
        $canViewReports = $membership->hasPermission('view_reports');

        $organization->load(['members.user', 'members.roles', 'roles', 'features']);

        $totalMembers = $organization->members->count();
        $newMembers30d = $organization->members
            ->filter(fn ($m) => $m->joined_at && $m->joined_at->gte(now()->subDays(30)))
            ->count();

        $elevatedMembers = $organization->members
            ->filter(fn ($m) => $m->roles->contains(
                fn ($role) => $role->is_system || strtolower(trim($role->name)) !== 'member'
            ))
            ->count();

        $pendingJoinRequests = $organization->joinRequests()
            ->where('status', 'pending')
            ->with('user')
            ->oldest()
            ->get();

        $upcomingEvents = $organization->events()->where('starts_at', '>=', now())->count();

        $moduleCatalog = config('modules');
        $featuresByKey = $organization->features->keyBy('module_key');
        $modules = collect($moduleCatalog)->map(fn ($def, $key) => [
            'key' => $key,
            'name' => $def['name'],
            'is_enabled' => (bool) optional($featuresByKey->get($key))->is_enabled,
        ])->values();

        // The Attendance stat/eligibility only mean anything if the org has
        // actually turned the module on — otherwise they'd show stale or
        // misleading numbers for a feature the org has switched off.
        $attendanceEnabled = (bool) optional($featuresByKey->get('attendance'))->is_enabled;

        $attendanceIndex = $attendanceEnabled ? $this->computeAttendanceIndex($organization) : null;

        $eligibility = ($canViewReports && $attendanceEnabled) ? $this->computeEligibility($organization) : null;

        $roleBreakdown = $organization->roles->map(function ($role) use ($organization, $totalMembers) {
            $count = $organization->members->filter(fn ($m) => $m->roles->contains('id', $role->id))->count();

            return [
                'id' => $role->id,
                'name' => $role->name,
                'count' => $count,
                'percent' => $totalMembers > 0 ? round(($count / $totalMembers) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values();

        $activity = collect();

        foreach ($organization->members as $member) {
            if (! $member->user) {
                continue;
            }

            $activity->push([
                'key' => "member-{$member->id}",
                'actor' => $member->user->name,
                'message' => 'joined the organization',
                'timestamp' => $member->joined_at ?? $member->created_at,
            ]);
        }

        foreach ($organization->joinRequests()->whereIn('status', ['approved', 'denied'])->with(['user', 'responder'])->latest('responded_at')->limit(10)->get() as $decision) {
            $activity->push([
                'key' => "join-request-{$decision->id}",
                'actor' => $decision->responder->name ?? 'An officer',
                'message' => $decision->status === 'approved'
                    ? "approved {$decision->user?->name}'s request to join"
                    : "denied {$decision->user?->name}'s request to join",
                'timestamp' => $decision->responded_at,
            ]);
        }

        foreach ($organization->announcements()->with('author')->latest()->limit(5)->get() as $announcement) {
            $activity->push([
                'key' => "announcement-{$announcement->id}",
                'actor' => $announcement->author->name,
                'message' => "posted an announcement: \"{$announcement->title}\"",
                'timestamp' => $announcement->created_at,
            ]);
        }

        foreach ($organization->events()->with('creator')->latest()->limit(5)->get() as $event) {
            $activity->push([
                'key' => "event-{$event->id}",
                'actor' => $event->creator->name,
                'message' => "scheduled an event: \"{$event->title}\"",
                'timestamp' => $event->created_at,
            ]);
        }

        foreach ($organization->attendanceSessions()->whereNotNull('closed_at')->withCount([
            'records as present_count' => fn ($q) => $q->where('status', 'present'),
            'records as excused_count' => fn ($q) => $q->where('status', 'excused'),
            'records as absent_count' => fn ($q) => $q->where('status', 'absent'),
        ])->with('creator')->latest('closed_at')->limit(5)->get() as $session) {
            $activity->push([
                'key' => "attendance-{$session->id}",
                'actor' => $session->creator->name,
                'message' => "closed attendance session \"{$session->title}\" — {$session->present_count} present, {$session->excused_count} excused, {$session->absent_count} absent",
                'timestamp' => $session->closed_at,
            ]);
        }

        $activity = $activity
            ->filter(fn ($item) => $item['timestamp'] !== null)
            ->sortByDesc('timestamp')
            ->take(6)
            ->map(fn ($item) => [
                'key' => $item['key'],
                'actor' => $item['actor'],
                'message' => $item['message'],
                'timestamp' => $item['timestamp']->diffForHumans(),
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
                'logo_path' => $organization->logo_path,
            ],
            'canReview' => $canReview,
            'canViewReports' => $canViewReports && $attendanceEnabled,
            'eligibility' => $eligibility,
            'stats' => [
                'total_members' => $totalMembers,
                'new_members_30d' => $newMembers30d,
                'elevated_members' => $elevatedMembers,
                'pending_join_requests' => $pendingJoinRequests->count(),
                'enabled_modules' => $modules->where('is_enabled', true)->count(),
                'total_modules' => $modules->count(),
                'upcoming_events' => $upcomingEvents,
                'attendance_index' => $attendanceIndex,
            ],
            'pendingApprovals' => $canReview ? $pendingJoinRequests->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->user->name,
                'email' => $r->user->email,
                'requested_at' => $r->created_at->diffForHumans(),
            ])->values() : [],
            'activity' => $activity,
            'roles' => $roleBreakdown,
            'modules' => $modules,
        ]);
    }

    /**
     * A real CSV export of every active member's attendance record —
     * the report the Dashboard's "Download CSV Report" link points to.
     */
    public function exportAttendanceReport(Request $request, Organization $organization): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();

        $membership = $organization->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('roles.permissions')
            ->first();

        abort_unless($membership && $membership->hasPermission('view_reports'), 403);

        $members = $organization->members()
            ->where('is_active', true)
            ->with(['user', 'roles'])
            ->get();

        $attendanceByMember = AttendanceRecord::query()
            ->whereIn('organization_member_id', $members->pluck('id'))
            ->select('organization_member_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
            ->groupBy('organization_member_id')
            ->get()
            ->keyBy('organization_member_id');

        $filename = Str::slug($organization->name).'-attendance-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($members, $attendanceByMember) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Student ID', 'Role', 'Sessions Present', 'Sessions Total', 'Attendance Rate']);

            foreach ($members->sortBy(fn ($m) => $m->user->name) as $member) {
                $attendance = $attendanceByMember->get($member->id);
                $rate = $attendance && $attendance->total > 0
                    ? round(($attendance->present_count / $attendance->total) * 100).'%'
                    : 'No data';

                fputcsv($handle, [
                    $member->user->name,
                    $member->membership_number ?? '',
                    $member->roles->pluck('name')->implode(', ') ?: 'No role',
                    $attendance->present_count ?? 0,
                    $attendance->total ?? 0,
                    $rate,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function formatMembershipCard(OrganizationMember $member): array
    {
        return [
            'id' => $member->organization->id,
            'name' => $member->organization->name,
            'type' => $member->organization->type,
            'logo_path' => $member->organization->logo_path,
            'role' => $member->roles->pluck('name')->first(),
            'members_count' => $member->organization->members()->count(),
        ];
    }
}

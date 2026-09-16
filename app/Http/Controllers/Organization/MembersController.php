<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\AttendanceRecord;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembersController extends Controller
{
    use ChecksOrganizationPermission;

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_roster');

        $allMembers = $organization->members()
            ->where('is_active', true)
            ->with(['user', 'roles'])
            ->get();

        $attendanceByMember = AttendanceRecord::query()
            ->whereIn('organization_member_id', $allMembers->pluck('id'))
            ->select('organization_member_id')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
            ->groupBy('organization_member_id')
            ->get()
            ->keyBy('organization_member_id');

        $members = $allMembers
            ->sortBy(fn ($m) => $m->user->name)
            ->map(function ($member) use ($attendanceByMember) {
                $attendance = $attendanceByMember->get($member->id);

                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'membership_number' => $member->membership_number,
                    'joined_at' => $member->joined_at?->format('M j, Y'),
                    'role_id' => $member->roles->first()?->id,
                    'role_name' => $member->roles->first()?->name,
                    'attendance_rate' => $attendance ? round(($attendance->present_count / $attendance->total) * 100) : null,
                    'attendance_sessions' => $attendance->total ?? 0,
                ];
            })
            ->values();

        $roles = $organization->roles()->orderBy('name')->get(['id', 'name']);

        $elevatedMembers = $allMembers->filter(fn ($m) => $m->roles->contains(
            fn ($role) => $role->is_system || strtolower(trim($role->name)) !== 'member'
        ))->count();

        $pendingJoinRequests = $canManage
            ? $organization->joinRequests()->where('status', 'pending')->with('user')->oldest()->get()
            : collect();

        $orgAttendanceRate = $attendanceByMember->isNotEmpty()
            ? round(($attendanceByMember->sum('present_count') / $attendanceByMember->sum('total')) * 100, 1)
            : null;

        return Inertia::render('Organizations/Members/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'members' => $members,
            'roles' => $roles,
            'canManage' => $canManage,
            'currentMemberId' => $membership->id,
            'stats' => [
                'total_members' => $allMembers->count(),
                'elevated_members' => $elevatedMembers,
                'pending_join_requests' => $pendingJoinRequests->count(),
                'org_attendance_rate' => $orgAttendanceRate,
            ],
            'pendingApprovals' => $pendingJoinRequests->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->user->name,
                'email' => $r->user->email,
                'requested_at' => $r->created_at->diffForHumans(),
            ])->values(),
        ]);
    }

    public function updateRole(Request $request, Organization $organization, OrganizationMember $member): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        abort_unless($member->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'role_id' => ['nullable', 'string'],
        ]);

        $role = $validated['role_id']
            ? $organization->roles()->where('id', $validated['role_id'])->first()
            : null;

        $member->roles()->sync($role ? [$role->id] : []);

        return back()->with('success', "Updated {$member->user->name}'s role.");
    }

    public function destroy(Request $request, Organization $organization, OrganizationMember $member): RedirectResponse
    {
        $reviewer = $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        abort_unless($member->organization_id === $organization->id, 404);
        abort_if($member->id === $reviewer->id, 422, 'You cannot remove yourself from the organization.');

        $name = $member->user->name;
        $member->roles()->detach();
        $member->delete();

        return back()->with('success', "Removed {$name} from the organization.");
    }

    public function bulkDestroy(Request $request, Organization $organization): RedirectResponse
    {
        $reviewer = $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        $validated = $request->validate([
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['string'],
        ]);

        $members = $organization->members()
            ->whereIn('id', $validated['member_ids'])
            ->where('id', '!=', $reviewer->id)
            ->get();

        foreach ($members as $member) {
            $member->roles()->detach();
            $member->delete();
        }

        $count = $members->count();

        return back()->with('success', "Removed {$count} member".($count === 1 ? '' : 's').'.');
    }
}

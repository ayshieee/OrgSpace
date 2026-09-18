<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Mail\MemberAccountCreated;
use App\Models\AttendanceRecord;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\RosterRowValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                    'avatar_path' => $member->user->avatar_path,
                    'membership_number' => $member->membership_number,
                    'section' => $member->section,
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

    /**
     * Adds one member after onboarding, reusing the exact same provisioning
     * pipeline as the Setup Wizard's commit() (Onboarding\MembersController)
     * — same RosterRowValidator, same new-vs-existing-user handling, same
     * MemberAccountCreated email — so behavior is identical whether a
     * member was added during setup or later from this page.
     */
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'm_i' => ['nullable', 'string', 'max:5'],
            'surname' => ['required', 'string', 'max:100'],
            'student_id' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['nullable', 'string'],
        ]);

        $row = [
            'first_name' => $validated['first_name'],
            'm_i' => $validated['m_i'] ?? '',
            'surname' => $validated['surname'],
            'student_id' => $validated['student_id'],
            'email' => $validated['email'],
        ];

        $result = RosterRowValidator::validateBatch([$row], $organization)[0];

        if ($result['status'] === 'error') {
            return back()->withErrors($result['errors'])->withInput();
        }

        $fullName = trim($row['first_name'].' '.($row['m_i'] !== '' ? $row['m_i'].'. ' : '').$row['surname']);
        $newAccount = null;

        DB::transaction(function () use ($result, $row, $validated, $organization, $fullName, &$newAccount) {
            if ($result['status'] === 'new_user') {
                $password = Str::password(16);

                $user = User::create([
                    'name' => $fullName,
                    'email' => $row['email'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'must_change_password' => true,
                ]);

                $newAccount = ['user' => $user, 'password' => $password];
            } else {
                $user = User::whereRaw('lower(email) = ?', [strtolower($row['email'])])->firstOrFail();
            }

            $member = $organization->members()->create([
                'user_id' => $user->id,
                'membership_number' => $row['student_id'],
                'is_active' => true,
            ]);

            $role = $validated['role_id']
                ? $organization->roles()->where('is_system', false)->where('id', $validated['role_id'])->first()
                : null;

            if ($role) {
                $member->roles()->attach($role->id);
            }
        });

        if (! $newAccount) {
            return back()->with('success', "{$fullName} was added to the organization.");
        }

        try {
            Mail::to($newAccount['user']->email)->send(
                new MemberAccountCreated($organization, Auth::user(), $newAccount['user']->email, $newAccount['password'])
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('warning', "{$fullName} was added, but the login-credential email could not be sent. Use \"Resend Login Email\" once mail delivery is working.");
        }

        return back()->with('success', "{$fullName} was added and their login credentials were emailed.");
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

    public function updateSection(Request $request, Organization $organization, OrganizationMember $member): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        abort_unless($member->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'section' => ['nullable', 'string', 'max:255'],
        ]);

        $member->update(['section' => $validated['section'] ?: null]);

        return back()->with('success', "Updated {$member->user->name}'s section.");
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

    public function bulkUpdateRole(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        $validated = $request->validate([
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['string'],
            'role_id' => ['nullable', 'string'],
        ]);

        $role = $validated['role_id']
            ? $organization->roles()->where('id', $validated['role_id'])->first()
            : null;

        $members = $organization->members()->whereIn('id', $validated['member_ids'])->get();

        foreach ($members as $member) {
            $member->roles()->sync($role ? [$role->id] : []);
        }

        $count = $members->count();

        return back()->with('success', "Updated role for {$count} member".($count === 1 ? '' : 's').'.');
    }

    public function regenerateQr(Request $request, Organization $organization, OrganizationMember $member): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        abort_unless($member->organization_id === $organization->id, 404);

        $member->update([
            'qr_token' => Str::random(32),
            'qr_version' => $member->qr_version + 1,
        ]);

        return back()->with('success', "Regenerated {$member->user->name}'s QR code.");
    }

    /**
     * Rotates a member's password and re-sends the credentials email — the
     * recovery path for a member whose original wizard-generated password
     * never arrived (e.g. commit() flagged a delivery failure), or who
     * simply lost it before ever logging in. Real failure is surfaced, not
     * swallowed, unlike the original send inside the onboarding wizard.
     */
    public function resendCredentials(Request $request, Organization $organization, OrganizationMember $member): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        abort_unless($member->organization_id === $organization->id, 404);

        $password = Str::password(16);

        $member->user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ])->save();

        try {
            Mail::to($member->user->email)->send(
                new MemberAccountCreated($organization, Auth::user(), $member->user->email, $password)
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Generated a new password for {$member->user->name}, but the email could not be sent. Try again once mail delivery is working.");
        }

        return back()->with('success', "New login credentials sent to {$member->user->name}.");
    }

    public function export(Request $request, Organization $organization): StreamedResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_roster');

        $selectedIds = array_filter(explode(',', (string) $request->query('ids', '')));

        $members = $organization->members()
            ->where('is_active', true)
            ->when($selectedIds, fn ($q) => $q->whereIn('id', $selectedIds))
            ->with(['user', 'roles'])
            ->get()
            ->sortBy(fn ($m) => $m->user->name);

        $filename = Str::slug($organization->name).'-members-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($members) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Student ID', 'Role', 'Joined']);

            foreach ($members as $member) {
                fputcsv($handle, [
                    $member->user->name,
                    $member->user->email,
                    $member->membership_number ?? '',
                    $member->roles->first()?->name ?? '',
                    $member->joined_at?->format('Y-m-d') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

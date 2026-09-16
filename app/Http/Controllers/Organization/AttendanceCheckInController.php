<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page a member lands on after scanning an attendance session's QR
 * code with their own phone — self-service check-in, no adviser action
 * needed. Deliberately separate from AttendanceController: this is reached
 * by any active member of the org, not just those who can manage_attendance.
 */
class AttendanceCheckInController extends Controller
{
    public function show(Request $request, AttendanceSession $session): Response
    {
        $session->load('organization');
        $user = $request->user();

        $member = $session->organization->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $member) {
            return Inertia::render('Attendance/CheckIn', [
                'status' => 'not_a_member',
                'sessionTitle' => $session->title,
                'organizationName' => $session->organization->name,
            ]);
        }

        if ($session->isClosed()) {
            return Inertia::render('Attendance/CheckIn', [
                'status' => 'closed',
                'sessionTitle' => $session->title,
                'organizationName' => $session->organization->name,
            ]);
        }

        $record = $session->records()->firstOrCreate(
            ['organization_member_id' => $member->id],
            ['status' => 'absent']
        );

        $alreadyPresent = $record->status === 'present';

        $record->update([
            'status' => 'present',
            'marked_at' => now(),
            'marked_by' => $user->id,
        ]);

        return Inertia::render('Attendance/CheckIn', [
            'status' => $alreadyPresent ? 'already_checked_in' : 'checked_in',
            'sessionTitle' => $session->title,
            'organizationName' => $session->organization->name,
            'checkedInAt' => $record->marked_at->format('g:i A'),
        ]);
    }
}

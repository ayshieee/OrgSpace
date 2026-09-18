<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AttendanceRecord;
use App\Models\Organization;

/**
 * Shared attendance-rate math used by both the org dashboard's "Attendance
 * Reports" card and the Attendance page's "Eligibility Standing" card — one
 * source of truth so the two never disagree.
 */
trait ComputesAttendanceEligibility
{
    /**
     * Minimum attendance rate (%) for a member to count as "in good
     * standing" — an internal policy line, not something members configure
     * themselves.
     */
    protected const ELIGIBILITY_THRESHOLD = 75;

    /**
     * Org-wide present/total ratio across every attendance record, or null
     * if the org has no attendance records yet.
     */
    protected function computeAttendanceIndex(Organization $organization): ?float
    {
        $totals = AttendanceRecord::query()
            ->whereIn('organization_member_id', $organization->members()->pluck('id'))
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
            ->first();

        return $totals && $totals->total > 0
            ? round(($totals->present_count / $totals->total) * 100, 1)
            : null;
    }

    /**
     * How many active members are at/above the eligibility threshold, out
     * of those who have any attendance data at all.
     */
    protected function computeEligibility(Organization $organization): array
    {
        $rateByMember = AttendanceRecord::query()
            ->whereIn('organization_member_id', $organization->members()->pluck('id'))
            ->select('organization_member_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
            ->groupBy('organization_member_id')
            ->get();

        $membersWithData = $rateByMember->count();
        $eligibleCount = $rateByMember->filter(
            fn ($r) => ($r->present_count / $r->total) * 100 >= self::ELIGIBILITY_THRESHOLD
        )->count();

        return [
            'eligible' => $eligibleCount,
            'total_with_data' => $membersWithData,
            'percent' => $membersWithData > 0 ? round(($eligibleCount / $membersWithData) * 100, 1) : null,
            'threshold' => self::ELIGIBILITY_THRESHOLD,
        ];
    }
}

<?php

namespace App\Http\Controllers\Organization\Concerns;

use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Http\Request;

/**
 * Shared membership/permission resolution for org-scoped controllers whose
 * routes carry an explicit {organization} route-model-binding param — avoids
 * the "first active membership" ambiguity that middleware-based gating has
 * for users who belong to more than one organization.
 */
trait ChecksOrganizationPermission
{
    protected function membership(Request $request, Organization $organization): OrganizationMember
    {
        $membership = $organization->members()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->with('roles.permissions')
            ->first();

        abort_unless($membership, 403);

        return $membership;
    }

    protected function authorizeOrgPermission(Request $request, Organization $organization, string $permission): OrganizationMember
    {
        $membership = $this->membership($request, $organization);

        abort_unless($membership->hasPermission($permission), 403);

        return $membership;
    }
}

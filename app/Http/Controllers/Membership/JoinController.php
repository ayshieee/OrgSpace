<?php

namespace App\Http\Controllers\Membership;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JoinController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();

        $pendingOrgIds = $user->joinRequests()
            ->where('status', 'pending')
            ->pluck('organization_id');

        $organizations = Organization::query()
            ->where('is_public', true)
            ->where('status', 'active')
            // Don't show orgs the user already belongs to — nothing to join.
            ->whereDoesntHave('members', fn ($query) => $query->where('user_id', $user->id))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->withCount('members')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'logo_path'])
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'description' => $org->description,
                'logo_path' => $org->logo_path,
                'members_count' => $org->members_count,
                'already_requested' => $pendingOrgIds->contains($org->id),
            ]);

        return Inertia::render('Join/Index', [
            'organizations' => $organizations,
            'query' => $q,
        ]);
    }

    public function joinByCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $organization = Organization::where('join_code', strtoupper(trim($validated['code'])))
            ->where('status', 'active')
            ->first();

        if (! $organization) {
            return back()->withErrors(['code' => 'That join code is invalid or has expired.'])->withInput();
        }

        $user = $request->user();

        if ($organization->members()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['code' => 'You are already a member of this organization.']);
        }

        $organization->members()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        return redirect()->route('dashboard')->with('success', "You've joined {$organization->name}.");
    }

    public function requestToJoin(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->isActive() && $organization->isPublic(), 404);

        $user = $request->user();

        if ($organization->members()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['join' => 'You are already a member of this organization.']);
        }

        if ($organization->joinRequests()->where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return back()->withErrors(['join' => 'You already have a pending request to join this organization.']);
        }

        $organization->joinRequests()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Your request to join has been sent.');
    }
}

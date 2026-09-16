<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Onboarding\Concerns\AdvancesOnboarding;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RolesController extends Controller
{
    use AdvancesOnboarding;

    public function show(Request $request): Response
    {
        $organization = $this->draftOrganization($request);

        $roles = $organization->roles()
            ->where('is_system', false)
            ->with('permissions')
            ->get()
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('permission')->values(),
            ])
            ->values();

        return Inertia::render('Onboarding/Roles', [
            'organization' => $organization,
            'roles' => $roles,
            'templates' => config('permissions.templates'),
            'permissionKeys' => config('permissions.keys'),
            'fromReview' => $request->query('from') === 'review',
        ]);
    }

    public function store(Request $request)
    {
        $organization = $this->draftOrganization($request);

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*.id' => ['nullable', 'string'],
            'roles.*.name' => ['required', 'string', 'max:100'],
            'roles.*.permissions' => ['array'],
            'roles.*.permissions.*' => [Rule::in(array_keys(config('permissions.keys')))],
        ]);

        $canManageRoster = collect($validated['roles'])
            ->contains(fn ($role) => in_array('manage_roster', $role['permissions'] ?? [], true));

        if (! $canManageRoster) {
            return back()->withErrors([
                'roles' => 'At least one role must be able to manage the roster.',
            ])->withInput();
        }

        $submittedIds = [];

        foreach ($validated['roles'] as $roleData) {
            $role = null;

            if (! empty($roleData['id'])) {
                $role = $organization->roles()->where('is_system', false)->find($roleData['id']);
            }

            if (! $role) {
                $role = $organization->roles()->create([
                    'name' => $roleData['name'],
                    'slug' => $this->uniqueSlug($organization, $roleData['name']),
                    'is_system' => false,
                ]);
            } else {
                $role->update(['name' => $roleData['name']]);
            }

            $role->syncPermissions($roleData['permissions'] ?? []);
            $submittedIds[] = $role->id;
        }

        // Remove any non-system roles that were dropped client-side.
        $organization->roles()
            ->where('is_system', false)
            ->whereNotIn('id', $submittedIds)
            ->get()
            ->each(fn ($role) => $role->delete());

        $this->advanceTo($organization, 'members');

        return $this->redirectToNextStep($request, 'onboarding.members.show');
    }

    protected function uniqueSlug(Organization $organization, string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $i = 1;

        while ($organization->roles()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}

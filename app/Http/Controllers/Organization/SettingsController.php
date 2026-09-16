<?php

namespace App\Http\Controllers\Organization;

use App\Models\Organization;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');
        $organization->load(['roles.permissions', 'features']);

        $roles = $organization->roles->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('permission')->values(),
        ])->values();

        $featuresByKey = $organization->features->keyBy('module_key');
        $modules = collect(config('modules'))->map(fn ($def, $key) => [
            'key' => $key,
            'name' => $def['name'],
            'description' => $def['description'],
            'locked' => $def['locked'],
            'is_enabled' => (bool) optional($featuresByKey->get($key))->is_enabled,
        ])->values();

        return Inertia::render('Organization/Settings', [
            'organization' => $organization,
            'roles' => $roles,
            'permissionKeys' => config('permissions.keys'),
            'modules' => $modules,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_public' => ['required', 'boolean'],
        ]);

        $organization->update([
            'name' => $validated['name'],
            'is_public' => $validated['is_public'],
            'branding' => array_merge($organization->branding ?? [], [
                'primary_color' => $validated['primary_color'] ?? ($organization->branding['primary_color'] ?? null),
            ]),
        ]);

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }

            $path = $request->file('logo')->store("organizations/{$organization->id}", 'public');
            $organization->update(['logo_path' => $path]);
        }

        return back()->with('success', 'Organization settings updated.');
    }

    public function generateJoinCode(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        $organization->update(['join_code' => Organization::generateUniqueJoinCode()]);

        return back()->with('success', 'New join code generated.');
    }

    public function updateRoles(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

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
            return back()->withErrors(['roles' => 'At least one role must be able to manage the roster.']);
        }

        $submittedIds = [];

        foreach ($validated['roles'] as $roleData) {
            $role = ! empty($roleData['id']) ? $organization->roles()->find($roleData['id']) : null;

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

        $organization->roles()->whereNotIn('id', $submittedIds)->get()->each(fn ($role) => $role->delete());

        return back()->with('success', 'Roles & permissions updated.');
    }

    public function updateModules(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.key' => ['required', 'string'],
            'modules.*.is_enabled' => ['boolean'],
        ]);

        $catalog = config('modules');

        foreach ($validated['modules'] as $moduleData) {
            if (! isset($catalog[$moduleData['key']]) || $catalog[$moduleData['key']]['locked']) {
                continue;
            }

            $organization->features()->updateOrCreate(
                ['module_key' => $moduleData['key']],
                ['is_enabled' => (bool) ($moduleData['is_enabled'] ?? false)]
            );
        }

        return back()->with('success', 'Feature modules updated.');
    }

    public function archive(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        $organization->delete();

        return redirect()->route('dashboard')->with('success', "{$organization->name} has been archived.");
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

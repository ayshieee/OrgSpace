<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Onboarding\Concerns\AdvancesOnboarding;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationProfileController extends Controller
{
    use AdvancesOnboarding;

    public function show(Request $request): Response
    {
        $organization = $this->draftOrganization($request);

        return Inertia::render('Onboarding/Profile', [
            'organization' => $organization,
            'types' => config('organization.types'),
            'fromReview' => $request->query('from') === 'review',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(config('organization.types')))],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $organization = $this->draftOrganization($request);

        if (! $organization) {
            $organization = Organization::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name'].'-'.Str::random(4)),
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'plan_type' => 'free',
                'status' => 'draft',
                'onboarding_step' => 'profile',
                'settings' => ['enabled_modules' => []],
            ]);

            // Seed the three starter roles up front (instead of lazily on
            // first visit to the Roles step) so the creator can be attached
            // to a real, visible role right away — there's no hidden
            // "Owner" role anymore. The creator's chosen role at sign-up
            // (adviser/officer) decides which one they land in; "adviser"
            // already carries every permission, so it's a safe default.
            $templateRoles = [];

            foreach (config('permissions.templates') as $key => $template) {
                $role = Role::create([
                    'organization_id' => $organization->id,
                    'name' => $template['name'],
                    'slug' => Str::slug($template['name']),
                    'is_system' => false,
                ]);

                $role->syncPermissions($template['permissions']);
                $templateRoles[$key] = $role;
            }

            $member = $organization->members()->create([
                'user_id' => Auth::id(),
                'is_active' => true,
            ]);

            $intendedRole = session()->pull('intended_role', 'adviser');
            $creatorRole = $templateRoles[$intendedRole] ?? $templateRoles['adviser'];

            $member->roles()->attach($creatorRole->id);
        } else {
            $organization->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
            ]);
        }

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }

            $path = $request->file('logo')->store("organizations/{$organization->id}", 'public');
            $organization->update(['logo_path' => $path]);
        }

        $this->advanceTo($organization, 'roles');

        return $this->redirectToNextStep($request, 'onboarding.roles.show');
    }
}

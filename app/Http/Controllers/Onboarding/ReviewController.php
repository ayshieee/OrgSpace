<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Onboarding\Concerns\AdvancesOnboarding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    use AdvancesOnboarding;

    public function show(Request $request): Response
    {
        $organization = $this->draftOrganization($request);
        $organization->load(['roles.permissions', 'members.user', 'members.roles', 'features']);

        return Inertia::render('Onboarding/Review', [
            'organization' => $organization,
            'permissionKeys' => config('permissions.keys'),
            'moduleCatalog' => config('modules'),
            'orgTypes' => config('organization.types'),
        ]);
    }

    public function activate(Request $request)
    {
        $organization = $this->draftOrganization($request);

        $organization->update([
            'status' => 'active',
            'onboarding_step' => 'completed',
            'activated_at' => now(),
        ]);

        session()->flash('activated_organization', $organization->name);

        return redirect()->route('onboarding.success');
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Onboarding/Success', [
            'organizationName' => session('activated_organization', 'Your organization'),
        ]);
    }
}

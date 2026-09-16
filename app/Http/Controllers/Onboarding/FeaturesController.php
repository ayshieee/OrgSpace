<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Onboarding\Concerns\AdvancesOnboarding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeaturesController extends Controller
{
    use AdvancesOnboarding;

    public function show(Request $request): Response
    {
        $organization = $this->draftOrganization($request);
        $existing = $organization->features()->get()->keyBy('module_key');

        $modules = collect(config('modules'))->map(function ($module, $key) use ($existing, $organization) {
            $feature = $existing->get($key);

            $defaultSettings = collect($module['sub_settings'])
                ->map(fn ($setting) => $setting['default'])
                ->all();

            return [
                'key' => $key,
                'name' => $module['name'],
                'description' => $module['description'],
                'category' => $module['category'],
                'locked' => $module['locked'],
                'sub_settings' => $module['sub_settings'],
                'recommended' => in_array($organization->type, $module['recommended_for'], true),
                'is_enabled' => $feature ? $feature->is_enabled : $module['locked'],
                'settings' => $feature?->settings ?? $defaultSettings,
            ];
        })->values();

        return Inertia::render('Onboarding/Features', [
            'organization' => $organization,
            'modules' => $modules,
            'fromReview' => $request->query('from') === 'review',
        ]);
    }

    public function store(Request $request)
    {
        $organization = $this->draftOrganization($request);
        $catalog = config('modules');

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.key' => ['required', 'string'],
            'modules.*.is_enabled' => ['boolean'],
            'modules.*.settings' => ['nullable', 'array'],
        ]);

        $anyEnabled = collect($validated['modules'])->contains(fn ($m) => $m['is_enabled'] ?? false)
            || collect($catalog)->contains(fn ($m) => $m['locked']);

        if (! $anyEnabled) {
            return back()->withErrors(['modules' => 'Enable at least one module.'])->withInput();
        }

        foreach ($validated['modules'] as $moduleData) {
            $key = $moduleData['key'];

            if (! isset($catalog[$key])) {
                continue;
            }

            $isEnabled = $catalog[$key]['locked'] ? true : (bool) ($moduleData['is_enabled'] ?? false);

            $organization->features()->updateOrCreate(
                ['module_key' => $key],
                ['is_enabled' => $isEnabled, 'settings' => $moduleData['settings'] ?? null]
            );
        }

        // Always ensure locked/core modules exist even if the client never sent them.
        foreach ($catalog as $key => $module) {
            if ($module['locked']) {
                $organization->features()->firstOrCreate(
                    ['module_key' => $key],
                    ['is_enabled' => true, 'settings' => null]
                );
            }
        }

        $enabledKeys = $organization->features()->where('is_enabled', true)->pluck('module_key')->all();
        $organization->update([
            'settings' => array_merge($organization->settings ?? [], ['enabled_modules' => $enabledKeys]),
        ]);

        $this->advanceTo($organization, 'review');

        return $this->redirectToNextStep($request, 'onboarding.review.show');
    }
}

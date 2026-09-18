<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\UserEducation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $request->user()->educations()->create($validated);

        return back()->with('success', 'Education added.');
    }

    public function update(Request $request, UserEducation $education): RedirectResponse
    {
        abort_unless($education->user_id === $request->user()->id, 404);

        $validated = $this->validated($request);

        $education->update($validated);

        return back()->with('success', 'Education updated.');
    }

    public function destroy(Request $request, UserEducation $education): RedirectResponse
    {
        abort_unless($education->user_id === $request->user()->id, 404);

        $education->delete();

        return back()->with('success', 'Education removed.');
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'institution' => ['required', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['boolean'],
        ]);

        if (! empty($validated['is_current'])) {
            $validated['end_date'] = null;
        }

        return $validated;
    }
}

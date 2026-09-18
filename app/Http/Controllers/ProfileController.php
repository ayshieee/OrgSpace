<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $memberships = $request->user()->organizationMemberships()
            ->with(['organization', 'roles'])
            ->get()
            ->map(fn ($membership) => [
                'id' => $membership->id,
                'organization_name' => $membership->organization->name,
                'logo_path' => $membership->organization->logo_path,
                'role' => $membership->roles->pluck('name')->join(', ') ?: 'No role',
                'student_id' => $membership->membership_number,
                'joined_at' => $membership->joined_at?->format('M j, Y'),
            ])
            ->values();

        $educations = $request->user()->educations()->orderByDesc('start_date')->get()
            ->map(fn ($education) => [
                'id' => $education->id,
                'institution' => $education->institution,
                'program' => $education->program,
                'start_date' => $education->start_date?->format('Y-m-d'),
                'end_date' => $education->end_date?->format('Y-m-d'),
                'is_current' => $education->is_current,
            ]);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'memberships' => $memberships,
            'educations' => $educations,
        ]);
    }

    /**
     * Replaces the user's profile picture, deleting the previous file (if
     * any) the same way OrganizationProfileController does for org logos.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();

        try {
            $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not upload your photo. Please try again.');
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $path]);

        return back()->with('success', 'Profile picture updated.');
    }

    /**
     * Removes the user's profile picture entirely, reverting them to the
     * initials avatar everywhere it's displayed.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return back()->with('success', 'Profile picture removed.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

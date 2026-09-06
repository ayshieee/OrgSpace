<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'organization_name' => 'required|string|max:255', // New field!
        ]);

        // 1. Create the Global User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 2. Create the Organization (Tenant)
        $organization = Organization::create([
            'name' => $request->organization_name,
            'slug' => Str::slug($request->organization_name . '-' . Str::random(4)),
            'plan_type' => 'free',
            'settings' => [
                'enabled_modules' => ['dashboard', 'members', 'groups', 'announcements'], // Basic defaults
            ]
        ]);

        // 3. Create the Default "Owner" Role for this specific organization
        $ownerRole = Role::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'slug' => 'owner',
            'is_system' => true, // Prevents accidental deletion
        ]);

        // 4. Attach the User to the Organization and assign the Owner role
        $memberRecord = $organization->members()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        
        $memberRecord->roles()->attach($ownerRole->id);

        event(new Registered($user));

        Auth::login($user);

        // Redirect them to the dashboard
        return redirect(route('setup.show', absolute: false));
    }
}
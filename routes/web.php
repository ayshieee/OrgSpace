<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Http\Request; // Added for handling the Setup form submission
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/workspace/setup', function () {
    // This looks for resources/js/Pages/Workspace/Setup.vue
    return Inertia::render('Workspace/Setup');
})->name('setup.index');

// Workspace Setup (Onboarding)
Route::get('/setup', function () {
    $user = Auth::user();
    $membership = $user->organizationMemberships()->with('organization')->first();
    
    // If they don't have an org, or somehow skipped registration, send them home
    if (!$membership) return redirect('/');

    return Inertia::render('Workspace/Setup', [
        'organization' => $membership->organization
    ]);
})->middleware(['auth', 'verified'])->name('setup.show');

Route::post('/setup', function (Request $request) {
    $user = Auth::user();
    
    // Ensure the user actually has a membership before trying to update it
    $membership = $user->organizationMemberships()->first();
    
    if ($membership) {
        $organization = $membership->organization;

        // Save the selected modules to the JSON settings column
        $organization->update([
            'settings' => [
                'enabled_modules' => $request->input('modules', ['dashboard', 'members'])
            ]
        ]);
    }

    return redirect()->route('dashboard');
})->middleware(['auth', 'verified'])->name('setup.store');

// Main Dashboard
Route::get('/dashboard', function () {
    $user = Auth::user();
    
    // Grab the user's active organization
    $membership = $user->organizationMemberships()->with('organization')->first();
    $organization = $membership ? $membership->organization : null;

    // Calculate real statistics
    $stats = [
        // Count real members in the database, default to 0 if no org
        'total_members' => $organization ? $organization->members()->count() : 0,
        
        // We will hardcode these two for now until we build the Events and Attendance tables
        'upcoming_events' => 0, 
        'avg_attendance' => '0%',
    ];

    return Inertia::render('Dashboard', [
        'stats' => $stats
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
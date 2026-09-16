<?php

use App\Http\Controllers\GetStartedController;
use App\Http\Controllers\Membership\JoinApprovalController;
use App\Http\Controllers\Membership\JoinController;
use App\Http\Controllers\Onboarding\FeaturesController;
use App\Http\Controllers\Onboarding\MembersController;
use App\Http\Controllers\Onboarding\OrganizationProfileController;
use App\Http\Controllers\Onboarding\ReviewController;
use App\Http\Controllers\Onboarding\RolesController;
use App\Http\Controllers\Organization\AnnouncementsController;
use App\Http\Controllers\Organization\AttendanceCheckInController;
use App\Http\Controllers\Organization\AttendanceController;
use App\Http\Controllers\Organization\EventsController;
use App\Http\Controllers\Organization\FilesController;
use App\Http\Controllers\Organization\MembersController as OrgMembersController;
use App\Http\Controllers\Organization\SettingsController;
use App\Http\Controllers\OrganizationHubController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// First-time-user choice screen and Join an Organization flow
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/get-started', [GetStartedController::class, 'show'])->name('get-started.show');

    Route::prefix('join')->name('join.')->group(function () {
        Route::get('/', [JoinController::class, 'index'])->name('index');
        Route::post('/code', [JoinController::class, 'joinByCode'])->name('code');
        Route::post('/{organization}/request', [JoinController::class, 'requestToJoin'])->name('request');
    });

    Route::prefix('organization/settings')->name('organization.settings.')
        ->middleware('org.permission:manage_org_settings')
        ->group(function () {
            Route::get('/', [SettingsController::class, 'show'])->name('show');
            Route::post('/', [SettingsController::class, 'update'])->name('update');
            Route::post('/join-code', [SettingsController::class, 'generateJoinCode'])->name('join-code.generate');
            Route::post('/roles', [SettingsController::class, 'updateRoles'])->name('roles.update');
            Route::post('/modules', [SettingsController::class, 'updateModules'])->name('modules.update');
            Route::post('/archive', [SettingsController::class, 'archive'])->name('archive');
        });
});

// Organization Setup Wizard (Onboarding)
Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/profile', [OrganizationProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [OrganizationProfileController::class, 'store'])->name('profile.store');

    Route::middleware('onboarding.step:roles')->group(function () {
        Route::get('/roles', [RolesController::class, 'show'])->name('roles.show');
        Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    });

    Route::middleware('onboarding.step:members')->group(function () {
        Route::get('/members', [MembersController::class, 'show'])->name('members.show');
        Route::post('/members/import/validate', [MembersController::class, 'validateImport'])->name('members.import.validate');
        Route::post('/members/commit', [MembersController::class, 'commit'])->name('members.commit');
        Route::post('/members/skip', [MembersController::class, 'skip'])->name('members.skip');
        Route::delete('/members/{member}', [MembersController::class, 'destroy'])->name('members.destroy');
        Route::get('/members/template', [MembersController::class, 'template'])->name('members.template');
    });

    Route::middleware('onboarding.step:features')->group(function () {
        Route::get('/features', [FeaturesController::class, 'show'])->name('features.show');
        Route::post('/features', [FeaturesController::class, 'store'])->name('features.store');
    });

    Route::middleware('onboarding.step:review')->group(function () {
        Route::get('/review', [ReviewController::class, 'show'])->name('review.show');
        Route::post('/activate', [ReviewController::class, 'activate'])->name('activate');
    });

    Route::get('/success', [ReviewController::class, 'success'])->name('success');
});

// "My Organizations" hub — the landing page after login — and each
// organization's own dashboard, reached by clicking into it from the hub.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [OrganizationHubController::class, 'index'])->name('dashboard');
    Route::get('/organizations/{organization}/dashboard', [OrganizationHubController::class, 'show'])->name('organizations.dashboard');
    Route::get('/organizations/{organization}/reports/attendance.csv', [OrganizationHubController::class, 'exportAttendanceReport'])->name('organizations.reports.attendance');

    Route::prefix('organizations/{organization}/join-requests')->name('organizations.join-requests.')->group(function () {
        Route::post('/{joinRequest}/approve', [JoinApprovalController::class, 'approve'])->name('approve');
        Route::post('/{joinRequest}/deny', [JoinApprovalController::class, 'deny'])->name('deny');
    });

    Route::prefix('organizations/{organization}/members')->name('organizations.members.')->group(function () {
        Route::get('/', [OrgMembersController::class, 'index'])->name('index');
        Route::patch('/{member}/role', [OrgMembersController::class, 'updateRole'])->name('update-role');
        Route::delete('/{member}', [OrgMembersController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-remove', [OrgMembersController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

    Route::prefix('organizations/{organization}/announcements')->name('organizations.announcements.')->group(function () {
        Route::get('/', [AnnouncementsController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementsController::class, 'store'])->name('store');
        Route::patch('/{announcement}', [AnnouncementsController::class, 'update'])->name('update');
        Route::delete('/{announcement}', [AnnouncementsController::class, 'destroy'])->name('destroy');
        Route::post('/{announcement}/nudge', [AnnouncementsController::class, 'nudge'])->name('nudge');
    });

    Route::prefix('organizations/{organization}/events')->name('organizations.events.')->group(function () {
        Route::get('/', [EventsController::class, 'index'])->name('index');
        Route::post('/', [EventsController::class, 'store'])->name('store');
        Route::patch('/{event}', [EventsController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventsController::class, 'destroy'])->name('destroy');
        Route::post('/{event}/rsvp', [EventsController::class, 'rsvp'])->name('rsvp');
    });

    Route::prefix('organizations/{organization}/attendance')->name('organizations.attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/{session}', [AttendanceController::class, 'show'])->name('show');
        Route::patch('/{session}/records/{record}', [AttendanceController::class, 'updateRecord'])->name('records.update');
        Route::post('/{session}/close', [AttendanceController::class, 'close'])->name('close');
        Route::delete('/{session}', [AttendanceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('organizations/{organization}/files')->name('organizations.files.')->group(function () {
        Route::get('/', [FilesController::class, 'index'])->name('index');
        Route::post('/', [FilesController::class, 'store'])->name('store');
        Route::delete('/{file}', [FilesController::class, 'destroy'])->name('destroy');
        Route::post('/{file}/toggle-restriction', [FilesController::class, 'toggleRestriction'])->name('toggle-restriction');
        Route::post('/defaults', [FilesController::class, 'updateDefaults'])->name('defaults.update');
    });

    // Reached by scanning an attendance session's QR code with a phone —
    // identified by the session alone, no organization segment needed.
    Route::get('/attendance/{session}/check-in', [AttendanceCheckInController::class, 'show'])->name('attendance.check-in');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

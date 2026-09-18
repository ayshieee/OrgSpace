<?php

use App\Http\Controllers\GetStartedController;
use App\Http\Controllers\Membership\JoinApprovalController;
use App\Http\Controllers\Membership\JoinController;
use App\Http\Controllers\Onboarding\FeaturesController;
use App\Http\Controllers\Onboarding\MembersController;
use App\Http\Controllers\Onboarding\OrganizationProfileController;
use App\Http\Controllers\Onboarding\ReviewController;
use App\Http\Controllers\Onboarding\RolesController;
use App\Http\Controllers\Organization\AnnouncementCommentsController;
use App\Http\Controllers\Organization\AnnouncementReactionsController;
use App\Http\Controllers\Organization\AnnouncementsController;
use App\Http\Controllers\Organization\AttendanceCheckInController;
use App\Http\Controllers\Organization\AttendanceController;
use App\Http\Controllers\Organization\EventsController;
use App\Http\Controllers\Organization\FilesController;
use App\Http\Controllers\Organization\MicrosoftIntegrationController;
use App\Http\Controllers\Organization\MembersController as OrgMembersController;
use App\Http\Controllers\Organization\MusicController;
use App\Http\Controllers\Organization\SettingsController;
use App\Http\Controllers\OrganizationHubController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\Profile\EducationController;
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
    Route::get('/organizations/{organization}/reports/attendance.csv', [OrganizationHubController::class, 'exportAttendanceReport'])
        ->middleware('module.enabled:attendance')
        ->name('organizations.reports.attendance');

    Route::prefix('organizations/{organization}/join-requests')->name('organizations.join-requests.')->group(function () {
        Route::post('/{joinRequest}/approve', [JoinApprovalController::class, 'approve'])->name('approve');
        Route::post('/{joinRequest}/deny', [JoinApprovalController::class, 'deny'])->name('deny');
    });

    Route::prefix('organizations/{organization}/members')->name('organizations.members.')->group(function () {
        Route::get('/', [OrgMembersController::class, 'index'])->name('index');
        Route::post('/', [OrgMembersController::class, 'store'])->name('store');
        Route::get('/export', [OrgMembersController::class, 'export'])->name('export');
        Route::patch('/{member}/role', [OrgMembersController::class, 'updateRole'])->name('update-role');
        Route::delete('/{member}', [OrgMembersController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-remove', [OrgMembersController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/bulk-role', [OrgMembersController::class, 'bulkUpdateRole'])->name('bulk-update-role');
        Route::post('/{member}/regenerate-qr', [OrgMembersController::class, 'regenerateQr'])->name('regenerate-qr');
        Route::post('/{member}/resend-credentials', [OrgMembersController::class, 'resendCredentials'])->name('resend-credentials');
        Route::patch('/{member}/section', [OrgMembersController::class, 'updateSection'])->name('update-section');
    });

    Route::prefix('organizations/{organization}/announcements')->name('organizations.announcements.')
        ->middleware('module.enabled:announcements')->group(function () {
        Route::get('/', [AnnouncementsController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementsController::class, 'store'])->name('store');
        Route::patch('/{announcement}', [AnnouncementsController::class, 'update'])->name('update');
        Route::delete('/{announcement}', [AnnouncementsController::class, 'destroy'])->name('destroy');
        Route::post('/{announcement}/nudge', [AnnouncementsController::class, 'nudge'])->name('nudge');
        Route::get('/{announcement}/attachments/{attachment}', [AnnouncementsController::class, 'attachment'])->name('attachments.show');
        Route::get('/{announcement}/attachments/{attachment}/processed', [AnnouncementsController::class, 'attachmentProcessed'])->name('attachments.processed');
        Route::post('/{announcement}/comments', [AnnouncementCommentsController::class, 'store'])->name('comments.store');
        Route::delete('/{announcement}/comments/{comment}', [AnnouncementCommentsController::class, 'destroy'])->name('comments.destroy');
        Route::get('/{announcement}/comments/{comment}/attachments/{attachment}', [AnnouncementCommentsController::class, 'attachment'])->name('comments.attachments.show');
        Route::post('/{announcement}/reaction', [AnnouncementReactionsController::class, 'toggle'])->name('reaction.toggle');
    });

    Route::prefix('organizations/{organization}/events')->name('organizations.events.')
        ->middleware('module.enabled:events')->group(function () {
        Route::get('/', [EventsController::class, 'index'])->name('index');
        Route::post('/', [EventsController::class, 'store'])->name('store');
        Route::patch('/{event}', [EventsController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventsController::class, 'destroy'])->name('destroy');
        Route::post('/{event}/rsvp', [EventsController::class, 'rsvp'])->name('rsvp');
        Route::get('/{event}/image', [EventsController::class, 'image'])->name('image');
        Route::get('/{event}', [EventsController::class, 'show'])->name('show');
        Route::post('/{event}/broadcast-call-sheet', [EventsController::class, 'broadcastCallSheet'])->name('broadcast-call-sheet');
        Route::post('/{event}/roster/{member}/nudge', [EventsController::class, 'nudgeRsvp'])->name('roster.nudge');
        Route::post('/{event}/checklist', [EventsController::class, 'storeChecklistItem'])->name('checklist.store');
        Route::patch('/{event}/checklist/{item}', [EventsController::class, 'updateChecklistItem'])->name('checklist.update');
        Route::delete('/{event}/checklist/{item}', [EventsController::class, 'destroyChecklistItem'])->name('checklist.destroy');
    });

    Route::prefix('organizations/{organization}/attendance')->name('organizations.attendance.')
        ->middleware('module.enabled:attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/{session}', [AttendanceController::class, 'show'])->name('show');
        Route::patch('/{session}/records/{record}', [AttendanceController::class, 'updateRecord'])->name('records.update');
        Route::post('/{session}/scan', [AttendanceController::class, 'scan'])->name('scan');
        Route::post('/{session}/records/{record}/excuse', [AttendanceController::class, 'submitExcuse'])->name('excuse.submit');
        Route::post('/{session}/records/{record}/excuse/review', [AttendanceController::class, 'reviewExcuse'])->name('excuse.review');
        Route::post('/{session}/broadcast', [AttendanceController::class, 'broadcastReminder'])->name('broadcast');
        Route::post('/{session}/close', [AttendanceController::class, 'close'])->name('close');
        Route::delete('/{session}', [AttendanceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('organizations/{organization}/files')->name('organizations.files.')
        ->middleware('module.enabled:files')->group(function () {
        Route::get('/', [FilesController::class, 'index'])->name('index');
        Route::post('/', [FilesController::class, 'store'])->name('store');
        Route::post('/folders', [FilesController::class, 'storeFolder'])->name('folders.store');
        Route::get('/{file}', [FilesController::class, 'show'])->name('show');
        Route::get('/{file}/download', [FilesController::class, 'download'])->name('download');
        Route::patch('/{file}/rename', [FilesController::class, 'rename'])->name('rename');
        Route::delete('/{file}', [FilesController::class, 'destroy'])->name('destroy');
        Route::post('/{file}/toggle-restriction', [FilesController::class, 'toggleRestriction'])->name('toggle-restriction');
        Route::post('/{file}/permissions', [FilesController::class, 'updatePermissions'])->name('permissions.update');
        Route::post('/{file}/office/open', [MicrosoftIntegrationController::class, 'openInOffice'])->name('office.open');
        Route::post('/{file}/office/pull', [MicrosoftIntegrationController::class, 'pullUpdatedVersion'])->name('office.pull');
        Route::post('/defaults', [FilesController::class, 'updateDefaults'])->name('defaults.update');
    });

    Route::prefix('organizations/{organization}/microsoft')->name('organizations.microsoft.')
        ->middleware('module.enabled:files')->group(function () {
        Route::get('/connect', [MicrosoftIntegrationController::class, 'connect'])->name('connect');
        Route::post('/disconnect', [MicrosoftIntegrationController::class, 'disconnect'])->name('disconnect');
    });

    Route::prefix('organizations/{organization}/music')->name('organizations.music.')
        ->middleware('module.enabled:music_library')->group(function () {
        Route::get('/', [MusicController::class, 'index'])->name('index');
        Route::post('/', [MusicController::class, 'store'])->name('store');
        Route::delete('/{music}', [MusicController::class, 'destroy'])->name('destroy');
        Route::post('/{music}/toggle-restriction', [MusicController::class, 'toggleRestriction'])->name('toggle-restriction');
        Route::post('/{music}/favorite', [MusicController::class, 'toggleFavorite'])->name('favorite');
        Route::patch('/{music}/annotations', [MusicController::class, 'updateAnnotations'])->name('annotations');
        Route::post('/defaults', [MusicController::class, 'updateDefaults'])->name('defaults.update');
    });

    // Reached by scanning an attendance session's QR code with a phone —
    // identified by the session alone, no organization segment needed.
    Route::get('/attendance/{session}/check-in', [AttendanceCheckInController::class, 'show'])->name('attendance.check-in');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::post('/profile/education', [EducationController::class, 'store'])->name('profile.education.store');
    Route::patch('/profile/education/{education}', [EducationController::class, 'update'])->name('profile.education.update');
    Route::delete('/profile/education/{education}', [EducationController::class, 'destroy'])->name('profile.education.destroy');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationsController::class, 'index'])->name('index');
        Route::post('/read-all', [NotificationsController::class, 'markAllRead'])->name('read-all');
        Route::post('/{notification}/read', [NotificationsController::class, 'markRead'])->name('read');
        Route::delete('/{notification}', [NotificationsController::class, 'destroy'])->name('destroy');
    });

    // Azure AD requires one exact-match, fixed redirect URI per app
    // registration — it can't vary per organization, so this lives outside
    // any {organization} prefix; which org is being connected travels in
    // the encrypted `state` param instead (see MicrosoftIntegrationController).
    Route::get('/microsoft/callback', [MicrosoftIntegrationController::class, 'callback'])->name('microsoft.callback');
});

require __DIR__.'/auth.php';

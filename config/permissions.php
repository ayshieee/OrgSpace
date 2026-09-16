<?php

return [

    // The full catalog of assignable capability keys, shown as rows in the
    // Step 2 permissions matrix.
    'keys' => [
        'manage_org_settings' => 'Manage Organization Settings',
        'manage_roles' => 'Manage Roles & Permissions',
        'manage_roster' => 'Manage Roster (Add/Remove Members)',
        'manage_events' => 'Manage Events & Calendar',
        'manage_attendance' => 'Manage Attendance',
        'manage_announcements' => 'Manage Announcements',
        'manage_files' => 'Manage Files',
        'manage_finance' => 'Manage Finance & Dues',
        'manage_library' => 'Manage Music Library',
        'view_reports' => 'View Reports',
        'manage_modules' => 'Manage Feature Modules',
        'view_roster' => 'View Roster',
    ],

    // Template roles offered in Step 2, and seeded up front in Step 1 so the
    // org creator can be attached to a real role immediately (they pick
    // adviser or officer at sign-up) — there's no hidden "Owner" role.
    'templates' => [
        'adviser' => [
            'name' => 'Adviser',
            'permissions' => [
                'manage_org_settings',
                'manage_roles',
                'manage_roster',
                'manage_events',
                'manage_attendance',
                'manage_announcements',
                'manage_files',
                'manage_finance',
                'manage_library',
                'view_reports',
                'manage_modules',
                'view_roster',
            ],
        ],
        'officer' => [
            'name' => 'Officer',
            'permissions' => [
                'manage_roster',
                'manage_events',
                'manage_attendance',
                'manage_announcements',
                'manage_files',
                'view_reports',
                'view_roster',
            ],
        ],
        'member' => [
            'name' => 'Member',
            'permissions' => [
                'view_roster',
            ],
        ],
    ],

];

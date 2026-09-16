<?php

return [

    'member_management' => [
        'name' => 'Member Management',
        'description' => 'Core database of members and roles.',
        'category' => 'core',
        'locked' => true,
        'sub_settings' => [],
        'recommended_for' => [],
    ],

    'attendance' => [
        'name' => 'Attendance Tracking',
        'description' => 'Scan QR codes for fast meeting attendance.',
        'category' => 'standard',
        'locked' => false,
        'sub_settings' => [
            'qr_default' => [
                'label' => 'Default to QR check-in',
                'type' => 'boolean',
                'default' => true,
            ],
        ],
        'recommended_for' => ['sports_club', 'performing_arts', 'community_service'],
    ],

    'announcements' => [
        'name' => 'Announcements',
        'description' => 'Post updates and news to your organization.',
        'category' => 'standard',
        'locked' => false,
        'sub_settings' => [],
        'recommended_for' => ['academic', 'student_council', 'fraternity_sorority'],
    ],

    'files' => [
        'name' => 'Secure Files',
        'description' => 'Cloud storage for organization documents.',
        'category' => 'standard',
        'locked' => false,
        'sub_settings' => [],
        'recommended_for' => ['academic'],
    ],

    'events' => [
        'name' => 'Events & Calendar',
        'description' => 'Schedule and manage upcoming meetings.',
        'category' => 'standard',
        'locked' => false,
        'sub_settings' => [],
        'recommended_for' => ['academic', 'student_council', 'fraternity_sorority'],
    ],

    'music_library' => [
        'name' => 'Music Library',
        'description' => 'Sheet music storage with annotation and watermarking.',
        'category' => 'specialized',
        'locked' => false,
        'sub_settings' => [
            'annotation_enabled' => [
                'label' => 'Allow annotations on scores',
                'type' => 'boolean',
                'default' => true,
            ],
            'watermark_enabled' => [
                'label' => 'Watermark downloaded scores',
                'type' => 'boolean',
                'default' => false,
            ],
        ],
        'recommended_for' => ['performing_arts'],
    ],

    'finance' => [
        'name' => 'Finance & Dues',
        'description' => 'Track organization budgets and member fees.',
        'category' => 'specialized',
        'locked' => false,
        'sub_settings' => [],
        'recommended_for' => ['student_council', 'fraternity_sorority'],
    ],

];

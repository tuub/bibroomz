<?php

return [
    'general' => [
        'table' => [
            'actions' => 'Actions',
            'not_required' => 'Not required',
        ],
    ],
    'dashboard' => [
        'title' => 'Dashboard',
        'description' => '',
    ],
    'happenings' => [
        'index' => [
            'title' => 'Happenings',
            'description' => 'Create, edit, delete happenings',
            'table' => [
                'header' => [
                    'date' => 'Date',
                    'resource' => 'Resource',
                    'start_time' => 'Start time',
                    'end_time' => 'End time',
                    'user_01' => 'User #1',
                    'user_02' => 'User #2',
                    'is_verified' => 'Verified',
                    'is_over' => 'Over?',
                ],
                'actions' => [
                    'create' => 'Create happening',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'Happenings Form',
            'description' => 'Create / edit a happening',
            'general' => [
                'not_required' => 'Not required with this resource',
            ],
            'fields' => [
                'start_date' => [
                    'label' => 'Start Date',
                    'placeholder' => 'Your start date',
                    'hint' => 'About your start date',
                ],
                'start_time' => [
                    'label' => 'Start Time',
                    'placeholder' => 'Your start time',
                    'hint' => 'About your start time',
                ],
                'end_date' => [
                    'label' => 'End Date',
                    'placeholder' => 'Your end date',
                    'hint' => 'About your end date',
                ],
                'end_time' => [
                    'label' => 'End Time',
                    'placeholder' => 'Your end time',
                    'hint' => 'About your end time',
                ],
                'resource' => [
                    'label' => 'Resource',
                    'hint' => 'About your resource',
                ],
                'user_01' => [
                    'label' => 'User #1',
                    'placeholder' => 'Your user #1',
                    'hint' => 'About your user #1',
                ],
                'user_02' => [
                    'label' => 'User #2',
                    'placeholder' => 'Your user #2',
                    'hint' => 'About your user #2',
                ],
                'verifier' => [
                    'label' => 'Verifier',
                    'placeholder' => 'Your verifier',
                    'hint' => 'About your verifier',
                ],
                'is_verified' => [
                    'label' => 'Is verified?',
                    'hint' => 'About verification',
                ],
            ],
            'actions' => [
                'submit' => 'Save',
            ],
        ],
    ],
    'institutions' => [
        'index' => [
            'title' => 'Institutions',
            'description' => 'Create, edit, delete institutions and its settings & closings',
            'table' => [
                'header' => [
                    'title' => 'Title',
                    'short_title' => 'Short Title',
                    'slug' => 'URI-Slug',
                    'location' => 'Location',
                    'resources' => 'Resources',
                    'is_active' => 'Active?',
                ],
                'actions' => [
                    'create' => 'Create institution',
                    'edit' => 'Edit',
                    'closings' => 'Closings',
                    'settings' => 'Settings',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'Institution Form',
            'description' => 'Create / edit an institution',
            'general' => [
                'not_required' => 'Not required with this resource',
            ],
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'placeholder' => 'The institution title',
                    'hint' => 'About the institution title',
                ],
                'short_title' => [
                    'label' => 'Short Title',
                    'placeholder' => 'Short Title',
                    'hint' => 'About your short title',
                ],
                'slug' => [
                    'label' => 'URI-Slug',
                    'placeholder' => 'The slug',
                    'hint' => 'The slug for constructing URIs',
                ],
                'location' => [
                    'label' => 'Location',
                    'placeholder' => 'Address',
                    'hint' => 'About the location',
                ],
                'week_days' => [
                    'label' => 'Active week days',
                    'hint' => 'On which days the institution is open?',
                ],
                'home_uri' => [
                    'label' => 'Home URI',
                    'placeholder' => 'Website',
                    'hint' => 'About your home URI',
                ],
                'logo_uri' => [
                    'label' => 'Logo URI',
                    'placeholder' => 'URI to a logo',
                    'hint' => 'About the logo URI',
                ],
                'teaser_uri' => [
                    'label' => 'Teaser URI',
                    'placeholder' => 'URI to a teaser image',
                    'hint' => 'About the teaser URI',
                ],
                'is_active' => [
                    'label' => 'Is active?',
                    'hint' => 'About the is active',
                ],
            ],
            'actions' => [
                'submit' => 'Save',
            ],
        ],
    ],
    'resources' => [
        'index' => [
            'title' => 'Resources',
            'description' => 'Create, edit, delete resources and its business hours & closings',
            'table' => [
                'header' => [
                    'title' => 'Title',
                    'location' => 'Location',
                    'institution' => 'Institution',
                    'business_hours' => 'Business Hours',
                    'capacity' => 'Capacity',
                    'is_active' => 'Active?',
                    'is_verification_required' => 'Verification required?',
                ],
                'actions' => [
                    'create' => 'Create resource',
                    'edit' => 'Edit',
                    'closings' => 'Closings',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'Resource Form',
            'description' => 'Create / edit a resource',
            'fields' => [
                'institution' => [
                    'label' => 'Institution',
                    'placeholder' => 'The institution',
                    'hint' => 'About the institution',
                ],
                'title' => [
                    'label' => 'Title',
                    'placeholder' => 'The resource title',
                    'hint' => 'About the resource title',
                ],
                'location' => [
                    'label' => 'Location',
                    'placeholder' => 'The location',
                    'hint' => 'About the location',
                ],
                'description' => [
                    'label' => 'Description',
                    'placeholder' => 'The description',
                    'hint' => 'About the description',
                ],
                'capacity' => [
                    'label' => 'Capacity',
                    'hint' => 'About the capacity',
                ],
                'is_active' => [
                    'label' => 'Is active?',
                    'hint' => 'About the is active',
                ],
                'is_verification_required' => [
                    'label' => 'Is verification required?',
                    'hint' => 'About the verification',
                ],
                'business_hours' => [
                    'label' => 'Business Hours # :index',
                    'hint' => 'About the business hours',
                    'subfields' => [
                        'week_days' => [
                            'label' => 'Week days',
                            'hint' => 'About the week days',
                        ],
                        'start' => [
                            'label' => 'Start',
                            'placeholder' => 'The Start time',
                            'hint' => 'About the start time',
                        ],
                        'end' => [
                            'label' => 'End',
                            'placeholder' => 'The end time',
                            'hint' => 'About the end time',
                        ],
                    ]
                ],
            ],
            'actions' => [
                'add_business_hours' => 'Add Business Hours',
                'submit' => 'Save',
            ],
        ],
    ],
    'users' => [
        'index' => [
            'title' => 'Users',
            'description' => 'Create, edit, delete users',
            'table' => [
                'header' => [
                    'name' => 'Username',
                    'email' => 'E-Mail',
                    'is_admin' => 'Admin?',
                    'is_banned' => 'Banned?',
                    'happenings' => '# Happenings',
                    'institutions' => 'Administered Institutions',
                ],
                'actions' => [
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'User Form',
            'description' => 'Edit a user',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => 'About the name (read-only)',
                ],
                'email' => [
                    'label' => 'E-Mail',
                    'placeholder' => 'The email address',
                    'hint' => 'About the email address (read-only)',
                ],
                'is_admin' => [
                    'label' => 'Is Admin?',
                    'hint' => 'About the admin role',
                ],
                'institution_admin' => [
                    'label' => 'Institution Admin',
                    'hint' => 'About institution admins',
                ],
            ],
            'actions' => [
                'submit' => 'Save',
            ],
        ],
    ],
    'stats' => [
        'title' => 'Statistics',
        'description' => 'Visualize statistics'
    ],
    'closings' => [
        'index' => [
            'title' => 'Closings for :type ":title"',
            'description' => 'Create, edit, delete closings',
            'table' => [
                'header' => [
                    'start' => 'Start',
                    'end' => 'End',
                    'description' => 'Description',
                    'is_over' => 'Over?',
                ],
                'actions' => [
                    'create' => 'Create closing',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'Closing Form for :type ":title"',
            'description' => 'Create / edit a closing',
            'fields' => [
                'start_date' => [
                    'label' => 'Start Date',
                    'placeholder' => 'The start date',
                    'hint' => 'About the start date',
                ],
                'start_time' => [
                    'label' => 'Start Time',
                    'placeholder' => 'The start time',
                    'hint' => 'About the start time',
                ],
                'end_date' => [
                    'label' => 'End Date',
                    'placeholder' => 'The end date',
                    'hint' => 'About the end date',
                ],
                'end_time' => [
                    'label' => 'End time',
                    'placeholder' => 'The end time',
                    'hint' => 'About the end time',
                ],
                'description' => [
                    'label' => 'Description',
                    'placeholder' => 'The description',
                    'hint' => 'About the description',
                ],
            ],
            'actions' => [
                'submit' => 'Save',
            ],
        ],
    ],
    'settings' => [
        'keys' => [
            'timezone' => [
                'label' => 'Time zone',
                'description' => '',
            ],
            'start_time_slot' => [
                'label' => 'Start time slot',
                'description' => '',
            ],
            'end_time_slot' => [
                'label' => 'End time slot',
                'description' => '',
            ],
            'time_slot_length' => [
                'label' => 'Length of time slot',
                'description' => '',
            ],
            'weeks_in_advance' => [
                'label' => 'Weeks in advance',
                'description' => '',
            ],
            'quota_weekly_happenings' => [
                'label' => 'Quota weekly happenings',
                'description' => '',
            ],
            'quota_daily_hours' => [
                'label' => 'Quota daily hours',
                'description' => '',
            ],
            'quota_weekly_hours' => [
                'label' => 'Quota weekly hours',
                'description' => '',
            ],
            'quota_happening_block_hours' => [
                'label' => 'Quota happening block hours',
                'description' => '',
            ],
            'date_format' => [
                'label' => 'Date format',
                'description' => '',
            ],
            'time_format' => [
                'label' => 'Time format',
                'description' => '',
            ],
        ],
        'index' => [
            'title' => 'Settings for institution ":title"',
            'description' => 'Edit settings',
            'table' => [
                'header' => [
                    'key' => 'Key',
                    'description' => 'Description',
                    'value' => 'Value',
                ],
                'actions' => [
                    'edit' => 'Edit',
                ],
            ],
        ],
    ],
];

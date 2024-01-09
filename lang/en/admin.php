<?php

return [
    'general' => [
        'label' => [
            'clone' => '(COPY)'
        ],
        'form' => [
            'choose' => 'Please choose',
            'toggle_password' => 'Toggle password',
            'submit' => 'Save',
            'cancel' => 'Cancel',
        ],
        'table' => [
            'actions' => 'Actions',
            'not_required' => 'Not required',
        ],
        'week_days' => [
            'monday' => [
                'label' => 'Monday',
                'short_label' => 'Mo',
            ],
            'tuesday' => [
                'label' => 'Tuesday',
                'short_label' => 'Tue',
            ],
            'wednesday' => [
                'label' => 'Wednesday',
                'short_label' => 'Wed',
            ],
            'thursday' => [
                'label' => 'Thurday',
                'short_label' => 'Thu',
            ],
            'friday' => [
                'label' => 'Friday',
                'short_label' => 'Fr',
            ],
            'saturday' => [
                'label' => 'Saturday',
                'short_label' => 'Sat',
            ],
            'sunday' => [
                'label' => 'Sunday',
                'short_label' => 'Sun'
            ],
        ]
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
                    'institution' => 'Institution',
                    'resource_group' => 'Group',
                    'resource' => 'Resource',
                    'start_time' => 'Start time',
                    'end_time' => 'End time',
                    'user1' => 'User #1',
                    'user2' => 'User #2',
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
                    'mails' => 'Emails',
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
                'location_uri' => [
                    'label' => 'Location URI',
                    'placeholder' => 'The location link',
                    'hint' => 'A link to more information on this resource',
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
                'email' => [
                    'label' => 'Email',
                    'placeholder' => 'Email',
                    'hint' => 'Contact email address',
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
        ],
    ],
    'resource_groups' => [
        'index' => [
            'title' => 'Ressource Groups',
            'description' => 'Create, edit, delete resource groups',
            'table' => [
                'header' => [
                    'title' => 'Title',
                    'slug' => 'Slug',
                    'institution' => 'Institution',
                    'description' => 'Description',
                    'is_active' => 'Active?',
                ],
                'actions' => [
                    'create' => 'Create resource group',
                    'edit' => 'Edit',
                    'resources' => 'Resources',
                    'settings' => 'Settings',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'Resource group form',
            'description' => 'Create / edit a resource group',
            'fields' => [
                'institution' => [
                    'label' => 'Institution',
                    'placeholder' => 'Institution',
                    'hint' => ' ',
                ],
                'title' => [
                    'label' => 'Title',
                    'placeholder' => 'Title',
                    'hint' => ' ',
                ],
                'slug' => [
                    'label' => 'Slug',
                    'placeholder' => 'URI-Slug of the resource group',
                    'hint' => ' ',
                ],
                'term_singular' => [
                    'label' => 'Singular',
                    'placeholder' => 'Singular',
                    'hint' => ' ',
                ],
                'term_plural' => [
                    'label' => 'Plural',
                    'placeholder' => 'Plural',
                    'hint' => ' ',
                ],
                'description' => [
                    'label' => 'Description',
                    'placeholder' => 'Description',
                    'hint' => ' ',
                ],
                'is_active' => [
                    'label' => 'Active?',
                    'hint' => ' ',
                ],
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
                    'clone' => 'Copy',
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
                'location_uri' => [
                    'label' => 'Location URI',
                    'placeholder' => 'The lcoation link',
                    'hint' => 'A link with further information for location',
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
                    'is_system_user' => 'System User?',
                    'is_admin' => 'Admin?',
                    'is_logged_in' => 'Logged in?',
                    'happenings_count' => '# Happenings',
                    'is_privileged' => 'Privileged?',
                    'is_banned' => 'Banned?',
                ],
                'actions' => [
                    'create' => 'Create system user',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                    'ban' => 'Ban',
                    'unban' => 'Unban',
                ],
            ],
        ],
        'form' => [
            'title' => 'User Form',
            'description' => 'Edit a user',
            'fields' => [
                'is_system_user' => [
                    'label' => 'System user?',
                    'hint' => 'System users can log-in without status validation.',
                ],
                'name' => [
                    'label' => 'Name',
                    'placeholder' => 'Name',
                    'hint' => 'The users name.',
                ],
                'email' => [
                    'label' => 'Email',
                    'placeholder' => 'Email address',
                    'hint' => 'The email address.',
                ],
                'is_set_password' => [
                    'label' => 'Create/edit password?',
                    'hint' => 'System users need a password.',
                ],
                'current_password' => [
                    'label' => 'Current password',
                    'placeholder' => 'The current password',
                    'hint' => 'Provide the current password here.',
                ],
                'password' => [
                    'label' => 'New password',
                    'placeholder' => 'The new password',
                    'hint' => 'Provide a new password here.',
                ],
                'password_confirm' => [
                    'label' => 'Password confirmation',
                    'placeholder' => 'The new password for confirmation',
                    'hint' => 'Provide rhe new password once more for confirmation.',
                ],
                'is_admin' => [
                    'label' => 'Is Admin?',
                    'hint' => 'Admins have full access.',
                ],
                'roles' => [
                    'label' => 'Roles',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'stats' => [
        'title' => 'Statistics',
        'description' => 'Visualize statistics'
    ],
    'closings' => [
        'types' => [
            'institution' => 'institution',
            'resource_group' => 'resource group',
            'resource' => 'resource',
        ],
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
        ],
    ],
    'settings' => [
        'types' => [
            'institution' => 'institution',
            'resource_group' => 'resource group',
        ],
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
            'cleanup_interval' => [
                'label' => 'Cleanup interval',
                'description' => '',
            ],
        ],
        'index' => [
            'title' => 'Settings for :type ":title"',
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
        'form' => [
            'title' => 'Settings for :type ":title"',
            'description' => 'Edit settings',
            'fields' => [
                'key' => [
                    'label' => 'Key',
                    'placeholder' => 'Key',
                    'hint' => ' ',
                ],
                'value' => [
                    'label' => 'Value',
                    'placeholder' => 'Value',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'roles' => [
        'index' => [
            'title' => 'Roles',
            'description' => 'Create, edit, delete roles',
            'table' => [
                'header' => [
                    'name' => 'Name',
                    'description' => 'Description',
                    'permissions' => 'Permissions',
                ],
                'actions' => [
                    'create' => 'Create role',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'Role Form',
            'description' => 'Edit a role',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => ' ',
                ],
                'description' => [
                    'label' => 'Description',
                    'hint' => ' ',
                ],
                'permissions' => [
                    'label' => 'Permissions',
                    'hint' => ' ',
                ],
            ],
            'no_group' => 'Other Permissions',
        ],
    ],
    'permissions' => [
        'index' => [
            'title' => 'Permissions',
            'description' => 'Edit permissions',
            'table' => [
                'header' => [
                    'name' => 'Name',
                    'description' => 'Description',
                ],
                'actions' => [
                    'edit' => 'Edit',
                ],
            ],
        ],
        'form' => [
            'title' => 'Permission Form',
            'description' => 'Edit a permission',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => ' ',
                ],
                'description' => [
                    'label' => 'Description',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'permission_groups' => [
        'index' => [
            'title' => 'Permission Groups',
            'description' => 'Edit permission groups',
            'table' => [
                'header' => [
                    'name' => 'Name',
                    'description' => 'Description',
                ],
                'actions' => [
                    'edit' => 'Edit',
                ],
            ],
        ],
        'form' => [
            'title' => 'Permission Group Form',
            'description' => 'Edit a permission group',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => ' ',
                ],
                'description' => [
                    'label' => 'Description',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'mails' => [
        'mail_types' => [
            'happening_created' => 'Event created',
            'happening_created_with_verification' => 'Event with verification created',
            'happening_updated' => 'Event updated',
            'happening_deleted' => 'Event deleted',
            'happening_verified' => 'Event verified',
            'closing_created' => 'Closing created',
            'closing_updated' => 'Closing updated',
        ],
        'index' => [
            'title' => 'Emails for institution ":title"',
            'description' => 'All mails',
            'table' => [
                'header' => [
                    'mail_type' => 'Type',
                    'subject' => 'Subject',
                    'is_active' => 'Active?',
                ],
                'actions' => [
                    'create' => 'Create email',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                ],
            ],
        ],
        'form' => [
            'title' => 'Form for mails',
            'description' => 'Create and edit email texts.',
            'fields' => [
                'mail_type' => [
                    'label' => 'Type',
                    'hint' => 'Choose a mail type.',
                ],
                'subject' => [
                    'label' => 'Subject',
                    'placeholder' => 'The mail subject',
                    'hint' => 'The mail subject.',
                ],
                'title' => [
                    'label' => 'Title',
                    'placeholder' => 'The mail title',
                    'hint' => 'The mail title.',
                ],
                'salutation' => [
                    'label' => 'Salutation',
                    'placeholder' => 'Salutation text of the mail',
                    'hint' => 'The salutation text of the mail.',
                ],
                'intro' => [
                    'label' => 'Intro',
                    'placeholder' => 'Intro text of the mail',
                    'hint' => 'The intro text of the mail. Markdown is allowed.',
                ],
                'outro' => [
                    'label' => 'Outro',
                    'placeholder' => 'Outro text of the mail',
                    'hint' => 'The outro gtext of the mail. Markdown is allowed.',
                ],
                'action_uri' => [
                    'label' => 'Action Button URI',
                    'placeholder' => 'URI behind action button',
                    'hint' => 'An action URI for a button.',
                ],
                'action_uri_label' => [
                    'label' => 'Action Button URI Label',
                    'placeholder' => 'Label for action button',
                    'hint' => 'A label for the action button.',
                ],
                'farewell' => [
                    'label' => 'Farewell',
                    'placeholder' => 'Farewell text of the mail',
                    'hint' => 'The farewell text of the mail. Markdown is allowed.',
                ],
                'is_active' => [
                    'label' => 'Aktiv',
                    'hint' => 'Ist die Mail aktiv?',
                ],
            ],
        ],
    ],
];

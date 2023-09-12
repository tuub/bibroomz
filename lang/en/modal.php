<?php

return [
    'form' => [
        'fields' => [
            'start' => [
                'label' => 'Start',
                'placeholder' => 'Your start',
                'hint' => 'About your start',
            ],
            'end' => [
                'label' => 'End',
                'placeholder' => 'Your end',
                'hint' => 'About your end',
            ],
            'verifier' => [
                'label' => 'Confirmation',
                'placeholder' => 'Library Account (TU: TUB Account) 2nd person',
                'hint' => 'About your verifier',
            ],
        ],
    ],
    'info' => [
        'title' => 'Booking',
        'description' => 'Show Booking Information',
        'action' => [
            'ok' => 'OK',
        ],
    ],
    'create' => [
        'title' => 'Create Booking',
        'description' => 'Create new booking',
        'action' => [
            'create' => 'Create ',
        ],
    ],
    'edit' => [
        'title' => 'Edit Booking',
        'description' => 'Edit your booking',
        'action' => [
            'update' => 'Save.',
        ],
    ],
    'delete' => [
        'title' => 'Delete Booking',
        'description' => 'Delete your booking.',
        'action' => [
            'delete' => 'Delete ',
        ],
    ],
    'edit_delete' => [
        'title' => 'Adjust Booking',
        'description' => 'Adjust your booking.',
        'action' => [
            'update' => 'Save.',
            'delete' => 'Delete ',
        ],
    ],
    'verify' => [
        'title' => 'Verify Booking',
        'description' => 'Verify your booking',
        'action' => [
            'verify' => 'Verify ',
        ],
    ],
    'resource_info' => [
        'title' => 'Information about the room/the workbay :resource_title',
        'description' => 'Here you find information about the room/the workbay',
        'resource_capacity' => 'Capacity',
        'resource_description' => 'Description',
        'resource_location' => 'Location',
        'show' => 'Display location info',
        'hide' => 'Hide location info',
        'action' => [
            'ok' => 'OK',
        ],
    ],
];

<?php

return [
    'form' => [
        'fields' => [
            'start' => [
                'label' => 'Start',
                'placeholder' => 'Start of your reservation',
                'hint' => ' ',
            ],
            'end' => [
                'label' => 'End',
                'placeholder' => 'End of your reservation',
                'hint' => ' ',
            ],
            'verifier' => [
                'label' => 'Confirmation',
                'placeholder' => 'Library Account (TU: TUB Account) 2nd person',
                'hint' => ' ',
            ],
            'label' => [
                'de' => [
                    'label' => 'Notiz (optional, öffentlich sichtbar)',
                    'placeholder' => 'z.B. Name der Lerngruppe',
                    'hint' => ' ',
                ],
                'en' => [
                    'label' => 'Note (optional, publicly visible)',
                    'placeholder' => 'e.g. name of the study group',
                    'hint' => ' ',
                ],
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
            'create' => 'Create',
        ],
    ],
    'edit' => [
        'title' => 'Edit Booking',
        'description' => 'Edit your booking',
        'action' => [
            'update' => 'Save',
        ],
    ],
    'delete' => [
        'title' => 'Delete Booking',
        'description' => 'Delete your booking.',
        'action' => [
            'delete' => 'Delete',
        ],
    ],
    'edit_delete' => [
        'title' => 'Adjust Booking',
        'description' => 'Adjust your booking.',
        'action' => [
            'update' => 'Save.',
            'delete' => 'Delete',
        ],
    ],
    'verify' => [
        'title' => 'Verify Booking',
        'description' => 'Verify your booking',
        'action' => [
            'verify' => 'Verify',
        ],
    ],
    'resource_info' => [
        'title' => 'Info',
        'description' => ' ',
        'resource_title' => 'Title',
        'resource_location' => 'Location',
        'resource_capacity' => 'Capacity',
        'resource_description' => 'Description',
        'show' => 'Display location info',
        'hide' => 'Hide location info',
        'action' => [
            'ok' => 'OK',
        ],
    ],
];

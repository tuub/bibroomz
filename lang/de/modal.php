<?php

return [
    'form' => [
        'fields' => [
            'start' => [
                'label' => 'Beginn',
                'placeholder' => 'Beginn Ihrer Reservierung',
                'hint' => 'Start of your reservation',
            ],
            'end' => [
                'label' => 'Ende',
                'placeholder' => 'Ende Ihrer Reservierung',
                'hint' => 'End of your Reservation',
            ],
            'verifier' => [
                'label' => 'Bestätigung',
                'placeholder' => 'Bibliothekskonto (TU: TUB-Account) der 2. Person',
                'hint' => 'About your verifier',
            ],
        ],
    ],
    'info' => [
        'title' => 'Buchungsinfo',
        'description' => 'Buchungsinformationen anzeigen',
        'action' => [
            'ok' => 'OK',
        ],
    ],
    'create' => [
        'title' => 'Buchung erstellen',
        'description' => 'Erstellen Sie eine neue Buchung:',
        'action' => [
            'create' => 'Erstellen',
        ],
    ],
    'edit' => [
        'title' => 'Buchung bearbeiten',
        'description' => 'Bearbeiten Sie Ihre Buchung:',
        'action' => [
            'update' => 'Speichern',
        ],
    ],
    'delete' => [
        'title' => 'Buchung löschen',
        'description' => 'Löschen Sie Ihre Buchung',
        'action' => [
            'delete' => 'Löschen',
        ],
    ],
    'edit_delete' => [
        'title' => 'Buchung bearbeiten/löschen',
        'description' => 'Bearbeiten oder löschen Sie Ihre Buchung:',
        'action' => [
            'update' => 'Speichern',
            'delete' => 'Löschen',
        ],
    ],
    'verify' => [
        'title' => 'Buchung bestätigen',
        'description' => 'Bestätigen Sie Ihre Buchung:',
        'action' => [
            'verify' => 'Bestätigen',
        ],
    ],
    'resource_info' => [
        'title' => 'Informationen zum Raum/zur Workbay :resource_title',
        'description' => 'Hier finden Sie Informationen zu diesem Raum/dieser Workbay:',
        'resource_capacity' => 'Kapazität',
        'resource_description' => 'Beschreibung',
        'resource_location' => 'Standort',
        'show' => 'Standortinfo anzeigen',
        'hide' => 'Standortinfo verbergen',
        'action' => [
            'ok' => 'OK',
        ],
    ],
];

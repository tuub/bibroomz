<?php

declare(strict_types=1);

return [
    'form' => [
        'fields' => [
            'start' => [
                'label' => 'Beginn',
                'placeholder' => 'Beginn Ihrer Reservierung',
                'hint' => ' ',
            ],
            'end' => [
                'label' => 'Ende',
                'placeholder' => 'Ende Ihrer Reservierung',
                'hint' => ' ',
            ],
            'verifier' => [
                'label' => 'Bestätigung',
                'placeholder' => 'Bibliothekskonto (TU: TUB-Account) der 2. Person',
                'hint' => ' ',
            ],
            'user_id_01' => [
                'label' => 'Benutzer',
                'placeholder' => '— Benutzer wählen —',
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
        'title' => 'Buchungsinfo',
        'action' => [
            'ok' => 'OK',
        ],
    ],
    'create' => [
        'title' => 'Buchung erstellen',
        'action' => [
            'create' => 'Erstellen',
        ],
    ],
    'edit' => [
        'title' => 'Buchung bearbeiten',
        'action' => [
            'update' => 'Speichern',
        ],
    ],
    'delete' => [
        'title' => 'Buchung löschen',
        'action' => [
            'delete' => 'Löschen',
        ],
    ],
    'edit_delete' => [
        'title' => 'Buchung bearbeiten/löschen',
        'action' => [
            'update' => 'Speichern',
            'delete' => 'Löschen',
        ],
    ],
    'verify' => [
        'title' => 'Buchung bestätigen',
        'action' => [
            'verify' => 'Bestätigen',
        ],
    ],
    'resource_info' => [
        'title' => 'Information',
        'resource_title' => 'Bezeichnung',
        'resource_location' => 'Standort',
        'resource_capacity' => 'Kapazität',
        'resource_description' => 'Beschreibung',
        'show' => 'Standortinfo anzeigen',
        'hide' => 'Standortinfo verbergen',
        'action' => [
            'ok' => 'OK',
        ],
    ],
    'resource_group_info' => [
        'action' => [
            'ok' => 'OK',
        ],
    ],
];

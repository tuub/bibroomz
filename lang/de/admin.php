<?php

return [
    'general' => [
        'table' => [
            'actions' => 'Aktionen',
            'not_required' => 'Nicht erforderlich',
        ],
    ],
    'dashboard' => [
        'title' => 'Dashboard',
        'description' => '',
    ],
    'happenings' => [
        'index' => [
            'title' => 'Events',
            'description' => 'Events erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'date' => 'Datum',
                    'resource' => 'Ressource',
                    'start_time' => 'Startzeit',
                    'end_time' => 'Endzeit',
                    'user_01' => 'Benutzer Nr. 1',
                    'user_02' => 'Benutzer Nr. 2',
                    'is_verified' => 'Bestätigt?',
                    'is_over' => 'Vorbei?',
                ],
                'actions' => [
                    'create' => 'Event erstellen',
                    'edit' => 'Bearbeiten',
                    'delete' => 'Löschen',
                ],
            ],
        ],
        'form' => [
            'title' => 'Event-Formular',
            'description' => 'Event erstellen / bearbeiten',
            'general' => [
                'not_required' => 'Mit dieser Ressource nicht erforderlich',
            ],
            'fields' => [
                'start_date' => [
                    'label' => 'Startdatum',
                    'placeholder' => 'Ihr Startdatum',
                    'hint' => 'Über Ihr Startdatum',
                ],
                'start_time' => [
                    'label' => 'Startzeit',
                    'placeholder' => 'Ihre Startzeit',
                    'hint' => 'Über Ihre Startzeit',
                ],
                'end_date' => [
                    'label' => 'Enddatum',
                    'placeholder' => 'Ihr Enddatum',
                    'hint' => 'Über Ihr Enddatum',
                ],
                'end_time' => [
                    'label' => 'Endzeit',
                    'placeholder' => 'Ihre Endzeit',
                    'hint' => 'Über Ihre Endzeit',
                ],
                'resource' => [
                    'label' => 'Ressource',
                    'hint' => 'Über Ihre Ressource',
                ],
                'user_01' => [
                    'label' => 'Benutzer Nr. 1',
                    'placeholder' => 'Ihr Benutzer Nr. 1',
                    'hint' => 'Über Benutzer Nr. 1',
                ],
                'user_02' => [
                    'label' => 'Benutzer Nr. 2',
                    'placeholder' => 'Ihr Benutzer Nr. 2',
                    'hint' => 'Über Benutzer Nr. 2',
                ],
                'verifier' => [
                    'label' => 'Bestätiger*in',
                    'placeholder' => 'Ihr*e Bestätiger*in',
                    'hint' => 'Über Ihre*n Bestätiger*in',
                ],
                'is_verified' => [
                    'label' => 'Bestätigt?',
                    'hint' => 'Bestätigt ja/nein',
                ],
            ],
            'actions' => [
                'submit' => 'Speichern',
            ],
        ],
    ],
    'institutions' => [
        'index' => [
            'title' => 'Einrichtungen',
            'description' => 'Einrichtungen und ihre Einstellungen & Schließungen erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'title' => 'Titel',
                    'short_title' => 'Kurztitel',
                    'slug' => 'URI-Slug',
                    'location' => 'Standort',
                    'resources' => 'Ressourcen',
                    'is_active' => 'Aktiv?',
                ],
                'actions' => [
                    'create' => 'Einrichtung erstellen',
                    'edit' => 'Bearbeiten',
                    'closings' => 'Schließungen',
                    'settings' => 'Einstellungen',
                    'delete' => 'Löschen',
                ],
            ],
        ],
        'form' => [
            'title' => 'Einrichtungsformular',
            'description' => 'Einrichtung erstellen / bearbeiten',
            'general' => [
                'not_required' => 'Mit dieser Ressource nicht erforderlich',
            ],
            'fields' => [
                'title' => [
                    'label' => 'Titel',
                    'placeholder' => 'Der Einrichtungstitel',
                    'hint' => 'Über den Einrichtungstitel',
                ],
                'short_title' => [
                    'label' => 'Kurztitel',
                    'placeholder' => 'Kurztitel',
                    'hint' => 'Über Ihren Kurztitel',
                ],
                'slug' => [
                    'label' => 'URI-Slug',
                    'placeholder' => 'Der Slug',
                    'hint' => 'Der Slug zur URI-Konstruktion',
                ],
                'location' => [
                    'label' => 'Standort',
                    'placeholder' => 'Adresse',
                    'hint' => 'Über den Standort',
                ],
                'home_uri' => [
                    'label' => 'URI Startseite',
                    'placeholder' => 'Website',
                    'hint' => 'Über Ihre Startseiten-URI',
                ],
                'logo_uri' => [
                    'label' => 'URI Logo',
                    'placeholder' => 'URI zum Logo',
                    'hint' => 'Über die Logo-URI',
                ],
                'teaser_uri' => [
                    'label' => 'URI Teaser',
                    'placeholder' => 'URI zu einem Vorschaubild',
                    'hint' => 'Über die Teaser-URI',
                ],
                'is_active' => [
                    'label' => 'Aktiv?',
                    'hint' => 'Aktiv ja/nein',
                ],
            ],
            'actions' => [
                'submit' => 'Speichern',
            ],
        ],
    ],
    'resources' => [
        'index' => [
            'title' => 'Ressourcen',
            'description' => 'Ressourcen und ihre Geschäftszeiten & Schließungen erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'title' => 'Titel',
                    'location' => 'Standort',
                    'institution' => 'Einrichtung',
                    'business_hours' => 'Geschäftszeiten',
                    'capacity' => 'Kapazität',
                    'is_active' => 'Aktiv?',
                    'is_verification_required' => 'Bestätigung erforderlich?',
                ],
                'actions' => [
                    'create' => 'Ressource erstellen',
                    'edit' => 'Bearbeiten',
                    'closings' => 'Schließungen',
                    'delete' => 'Löschen',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Ressourcen',
            'description' => 'Ressource erstellen / bearbeiten',
            'fields' => [
                'institution' => [
                    'label' => 'Einrichtung',
                    'placeholder' => 'Die Einrichtung',
                    'hint' => 'Über die Einrichtung',
                ],
                'title' => [
                    'label' => 'Titel',
                    'placeholder' => 'Der Ressourcentitel',
                    'hint' => 'Über den Ressourcentitel',
                ],
                'location' => [
                    'label' => 'Standort',
                    'placeholder' => 'Der Standort',
                    'hint' => 'Über den Standort',
                ],
                'description' => [
                    'label' => 'Beschreibung',
                    'placeholder' => 'Die Beschreibung',
                    'hint' => 'Über die Beschreibung',
                ],
                'capacity' => [
                    'label' => 'Kapazität',
                    'hint' => 'Über die Kapazität',
                ],
                'is_active' => [
                    'label' => 'Aktiv?',
                    'hint' => 'Aktiv ja/nein',
                ],
                'is_verification_required' => [
                    'label' => 'Bestätigung erforderlich?',
                    'hint' => 'Bestätigung erforderlich ja/nein',
                ],
                'business_hours' => [
                    'label' => 'Geschäftszeiten Nr. :index',
                    'hint' => 'Über die Geschäftszeiten',
                    'subfields' => [
                        'week_days' => [
                            'label' => 'Wochentage',
                            'hint' => 'Über die Wochentage',
                        ],
                        'start' => [
                            'label' => 'Start',
                            'placeholder' => 'Die Startzeit',
                            'hint' => 'Über die Startzeit',
                        ],
                        'end' => [
                            'label' => 'Ende',
                            'placeholder' => 'Die Endzeit',
                            'hint' => 'Über die Endzeit',
                        ],
                    ]
                ],
            ],
            'actions' => [
                'add_business_hours' => 'Geschäftszeiten hinzufügen',
                'submit' => 'Speichern',
            ],
        ],
    ],
    'users' => [
        'index' => [
            'title' => 'Benutzer',
            'description' => 'Benutzer erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'name' => 'Benutzername',
                    'email' => 'E-Mail',
                    'is_admin' => 'Admin?',
                    'is_banned' => 'Gesperrt?',
                    'happenings' => '# Events',
                ],
                'actions' => [
                    'edit' => 'Bearbeiten',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Benutzer',
            'description' => 'Benutzer bearbeiten',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => 'Über den Namen (nur lesbar)',
                ],
                'email' => [
                    'label' => 'E-Mail',
                    'placeholder' => 'Die E-Mail-Adresse',
                    'hint' => 'Über die E-Mail-Adresse (nur lesbar)',
                ],
                'is_admin' => [
                    'label' => 'Admin?',
                    'hint' => 'Über die Admin-Rolle',
                ],
            ],
            'actions' => [
                'submit' => 'Speichern',
            ],
        ],
    ],
    'stats' => [
        'title' => 'Statistiken',
        'description' => 'Statistiken visuell darstellen',
    ],
    'closings' => [
        'index' => [
            'title' => 'Schließungen für :type ":title"',
            'description' => 'Schließungen erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'start' => 'Start',
                    'end' => 'Ende',
                    'description' => 'Beschreibung',
                    'is_over' => 'Vorbei?',
                ],
                'actions' => [
                    'create' => 'Schließung erstellen',
                    'edit' => 'Bearbeiten',
                    'delete' => 'Löschen',
                ],
            ],
        ],
        'form' => [
            'title' => 'Schließungsformular für :type ":title"',
            'description' => 'Schließung erstellen / bearbeiten',
            'fields' => [
                'start_date' => [
                    'label' => 'Startdatum',
                    'placeholder' => 'Das Startdatum',
                    'hint' => 'Über das Startdatum',
                ],
                'start_time' => [
                    'label' => 'Startzeit',
                    'placeholder' => 'Die Startzeit',
                    'hint' => 'Über die Startzeit',
                ],
                'end_date' => [
                    'label' => 'Enddatum',
                    'placeholder' => 'Das Enddatum',
                    'hint' => 'Über das Enddatum',
                ],
                'end_time' => [
                    'label' => 'Endzeit',
                    'placeholder' => 'Die Endzeit',
                    'hint' => 'Über die Endzeit',
                ],
                'description' => [
                    'label' => 'Beschreibung',
                    'placeholder' => 'Die Beschreibung',
                    'hint' => 'Über die Beschreibung',
                ],
            ],
            'actions' => [
                'submit' => 'Speichern',
            ],
        ],
    ],
    'settings' => [
        'keys' => [
            'timezone' => [
                'label' => 'Zeitzone',
                'description' => '',
            ],
            'start_time_slot' => [
                'label' => 'Startzeitfenster',
                'description' => '',
            ],
            'end_time_slot' => [
                'label' => 'Endzeitfenster',
                'description' => '',
            ],
            'time_slot_length' => [
                'label' => 'Länge des Zeitfensters',
                'description' => '',
            ],
            'weeks_in_advance' => [
                'label' => 'Wochen im Voraus',
                'description' => '',
            ],
            'quota_weekly_happenings' => [
                'label' => 'Wöchentliches Event-Kontingent',
                'description' => '',
            ],
            'quota_daily_hours' => [
                'label' => 'Tägliches Stunden-Kontingent',
                'description' => '',
            ],
            'quota_weekly_hours' => [
                'label' => 'Wöchentliches Stunden-Kontingent',
                'description' => '',
            ],
            'quota_happening_block_hours' => [
                'label' => 'Block-Stunden-Kontingent',
                'description' => '',
            ],
            'date_format' => [
                'label' => 'Datumsformat',
                'description' => '',
            ],
            'time_format' => [
                'label' => 'Zeitformat',
                'description' => '',
            ],
        ],
        'index' => [
            'title' => 'Einstellungen für Einrichtung ":title"',
            'description' => 'Einstellungen bearbeiten',
            'table' => [
                'header' => [
                    'key' => 'Schlüssel',
                    'description' => 'Beschreibung',
                    'value' => 'Wert',
                ],
                'actions' => [
                    'edit' => 'Bearbeiten',
                ],
            ],
        ],
    ],
];

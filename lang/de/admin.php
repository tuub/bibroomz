<?php

return [
    'general' => [
        'label' => [
            'clone' => '(KOPIE)'
        ],
        'form' => [
            'choose' => 'Bitte auswählen',
            'toggle_password' => 'Passwort ein-/ausblenden',
            'submit' => 'Speichern',
            'cancel' => 'Abbrechen',
        ],
        'table' => [
            'actions' => 'Aktionen',
            'not_required' => 'Nicht erforderlich',
            'keyword_search' => 'Suchbegriff',
            'no_records' => 'Keine Datensätze gefunden.',
            'loading_records' => 'Lade Datensätze ...'
        ],
        'week_days' => [
            'monday' => [
                'label' => 'Montag',
                'short_label' => 'Mo',
            ],
            'tuesday' => [
                'label' => 'Dienstag',
                'short_label' => 'Di',
            ],
            'wednesday' => [
                'label' => 'Mittwoch',
                'short_label' => 'Mi',
            ],
            'thursday' => [
                'label' => 'Donnerstag',
                'short_label' => 'Do',
            ],
            'friday' => [
                'label' => 'Freitag',
                'short_label' => 'Fr',
            ],
            'saturday' => [
                'label' => 'Samstag',
                'short_label' => 'Sa',
            ],
            'sunday' => [
                'label' => 'Sonntag',
                'short_label' => 'So'
            ],
        ]
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
                    'institution' => 'Einrichtung',
                    'resource_group' => 'Gruppe',
                    'resource' => 'Ressource',
                    'start_time' => 'Startzeit',
                    'end_time' => 'Endzeit',
                    'user1' => 'Benutzer Nr. 1',
                    'user2' => 'Benutzer Nr. 2',
                    'label' => 'Label',
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
                'label' => [
                    'label' => 'Label',
                    'hint' => 'Über das Label',
                ],
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
                    'mails' => 'E-Mails',
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
                'location_uri' => [
                    'label' => 'Location URI',
                    'placeholder' => 'The location link',
                    'hint' => 'A link to more information on this resource',
                ],
                'week_days' => [
                    'label' => 'Aktive Wochentage',
                    'hint' => 'An welchen Wochentagen ist geöffnet?',
                ],
                'home_uri' => [
                    'label' => 'URI Startseite',
                    'placeholder' => 'Website',
                    'hint' => 'Über Ihre Startseiten-URI',
                ],
                'email' => [
                    'label' => 'E-Mail-Adresse',
                    'placeholder' => 'E-Mail-Adresse',
                    'hint' => 'E-Mail-Adresse für Kontakt',
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
        ],
    ],
    'resource_groups' => [
        'index' => [
            'title' => 'Ressource-Gruppen',
            'description' => 'Ressource-Gruppen erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'title' => 'Titel',
                    'slug' => 'Slug',
                    'institution' => 'Einrichtung',
                    'description' => 'Beschreibung',
                    'is_active' => 'Aktiv?',
                ],
                'actions' => [
                    'create' => 'Ressource-Gruppe erstellen',
                    'edit' => 'Bearbeiten',
                    'resources' => 'Ressourcen',
                    'settings' => 'Einstellungen',
                    'delete' => 'Löschen',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Ressource-Gruppen',
            'description' => 'Ressource-Gruppe erstellen / bearbeiten',
            'fields' => [
                'institution' => [
                    'label' => 'Einrichtung',
                    'placeholder' => 'Die Einrichtung',
                    'hint' => 'Über die Einrichtung',
                ],
                'title' => [
                    'label' => 'Titel',
                    'placeholder' => 'Titel der Gruppe',
                    'hint' => 'Über den Titel der Ressoure-Gruppe',
                ],
                'slug' => [
                    'label' => 'Slug',
                    'placeholder' => 'URI-Slug der Ressoure-Gruppe',
                    'hint' => 'Über den URI-Slug der Ressoure-Gruppe',
                ],
                'term_singular' => [
                    'label' => 'Singular',
                    'placeholder' => 'Bezeichnung im Singular',
                    'hint' => 'Über die Bezeichnung im Singular',
                ],
                'term_plural' => [
                    'label' => 'Plural',
                    'placeholder' => 'Bezeichnung im Plural',
                    'hint' => 'Über die Bezeichnung im Plural',
                ],
                'description' => [
                    'label' => 'Beschreibung',
                    'placeholder' => 'Die Beschreibung',
                    'hint' => 'Über die Beschreibung',
                ],
                'help_uri' => [
                    'label' => 'Hilfe-URI',
                    'placeholder' => 'Hilfe-URI',
                    'hint' => 'Wird in der Sidebar verlinkt, wenn die Ressourcen-Gruppe nicht für alle Benutzer*innen verfügbar ist.',
                ],
                'is_active' => [
                    'label' => 'Aktiv?',
                    'hint' => 'Aktiv ja/nein',
                ],
                'user_groups' => [
                    'label' => 'Auf Benutzergruppen eingeschränkt?',
                    'hint' => 'Soll die Ressource-Gruppe nur für ausgewählte Benutzergruppen verfügbar sein?',
                    'placeholder' => 'Nicht eingeschränkt',
                ],
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
                    'clone' => 'Kopieren',
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
                'location_uri' => [
                    'label' => 'Standort URI',
                    'placeholder' => 'Der Standort Link',
                    'hint' => 'Ein Link zu weiteren Informationen zum Standort',
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
                        'start_date' => [
                            'label' => 'Startdatum',
                            'placeholder' => 'Das Startdatum',
                            'hint' => 'Über das Startdatum',
                        ],
                        'end_date' => [
                            'label' => 'Enddatum',
                            'placeholder' => 'Das Enddatum',
                            'hint' => 'Über das Enddatum',
                        ],
                    ]
                ],
            ],
            'actions' => [
                'add_business_hours' => 'Geschäftszeiten hinzufügen',
            ],
        ],
    ],
    'users' => [
        'index' => [
            'title' => 'Benutzer',
            'description' => 'Benutzer erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'name' => 'Benutzer*innen-Name',
                    'email' => 'E-Mail',
                    'is_system_user' => 'System-User?',
                    'is_admin' => 'Admin?',
                    'is_logged_in' => 'Angemeldet?',
                    'happenings_count' => '# Events',
                    'is_privileged' => 'Privilegiert?',
                    'is_banned' => 'Gesperrt?',
                ],
                'actions' => [
                    'create' => 'System-Benutzer*in erstellen',
                    'edit' => 'Bearbeiten',
                    'delete' => 'Löschen',
                    'ban' => 'Sperren',
                    'unban' => 'Entsperren',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Benutzer',
            'description' => 'Benutzer bearbeiten',
            'fields' => [
                'is_system_user' => [
                    'label' => 'System-Benutzer*in?',
                    'hint' => 'System-Benutzer*innen können sich ohne Statusprüfung einloggen.',
                ],
                'name' => [
                    'label' => 'Name',
                    'placeholder' => 'Name',
                    'hint' => 'Der Username.',
                ],
                'email' => [
                    'label' => 'E-Mail',
                    'placeholder' => 'E-Mail-Adresse',
                    'hint' => 'Die Email-Adresse.',
                ],
                'is_set_password' => [
                    'label' => 'Passwort erstellen/updaten?',
                    'hint' => 'System-Benutzer*innen müssen ein Passwort angeben.',
                ],
                'current_password' => [
                    'label' => 'Aktuelles Passwort',
                    'placeholder' => 'Das aktuelle Passwort',
                    'hint' => 'Hier wird das aktuelle Passwort angegeben.',
                ],
                'password' => [
                    'label' => 'Neues Passwort',
                    'placeholder' => 'Das neue Passwort',
                    'hint' => 'Hier wird ein neues Passwort angegeben.',
                ],
                'password_confirm' => [
                    'label' => 'Passwort bestätigen',
                    'placeholder' => 'Das neue Passwort zur Bestätigung',
                    'hint' => 'Hier wird erneut das neue Passwort angegeben.',
                ],
                'is_admin' => [
                    'label' => 'Admin?',
                    'hint' => 'Admins haben vollen Zugriff.',
                ],
                'roles' => [
                    'label' => 'Rollen',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'stats' => [
        'title' => 'Statistiken',
        'description' => 'Statistiken visuell darstellen',
    ],
    'closings' => [
        'types' => [
            'institution' => 'Einrichtung',
            'resource_group' => 'Ressourcen-Gruppe',
            'resource' => 'Resource',
        ],
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
            'title' => 'Schließzeit für :type ":title"',
            'description' => 'Schließzeit erstellen / bearbeiten',
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
        ],
    ],
    'settings' => [
        'types' => [
            'institution' => 'Einrichtung',
            'resource_group' => 'Ressourcen-Gruppe',
        ],
        'keys' => [
            'timezone' => [
                'label' => 'Zeitzone',
                'description' => ' ',
            ],
            'start_time_slot' => [
                'label' => 'Startzeitfenster',
                'description' => ' ',
            ],
            'end_time_slot' => [
                'label' => 'Endzeitfenster',
                'description' => ' ',
            ],
            'time_slot_length' => [
                'label' => 'Länge des Zeitfensters',
                'description' => ' ',
            ],
            'weeks_in_advance' => [
                'label' => 'Wochen im Voraus',
                'description' => ' ',
            ],
            'quota_weekly_happenings' => [
                'label' => 'Wöchentliches Event-Kontingent',
                'description' => ' ',
            ],
            'quota_daily_hours' => [
                'label' => 'Tägliches Stunden-Kontingent',
                'description' => ' ',
            ],
            'quota_weekly_hours' => [
                'label' => 'Wöchentliches Stunden-Kontingent',
                'description' => ' ',
            ],
            'quota_happening_block_hours' => [
                'label' => 'Block-Stunden-Kontingent',
                'description' => ' ',
            ],
            'date_format' => [
                'label' => 'Datumsformat',
                'description' => ' ',
            ],
            'time_format' => [
                'label' => 'Zeitformat',
                'description' => ' ',
            ],
            'cleanup_interval' => [
                'label' => 'Löschintervall',
                'description' => ' ',
            ],
            'is_label_enabled' => [
                'label' => 'Buchungslabels aktiviert?',
                'description' => 'Auf "1" setzen, um Buchungeslabels zu aktivieren.',
            ],
            'allowed_ips' => [
                'label' => 'Erlaubte IPs',
                'description' => ' ',
            ],
        ],
        'index' => [
            'title' => 'Einstellungen für :type ":title"',
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
        'form' => [
            'title' => 'Einstellung für :type ":title"',
            'description' => 'Einstellung bearbeiten',
            'fields' => [
                'key' => [
                    'label' => 'Schlüssel',
                    'placeholder' => 'Der Schlüssel',
                    'hint' => ' ',
                ],
                'value' => [
                    'label' => 'Wert',
                    'placeholder' => 'Der Wert',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'roles' => [
        'index' => [
            'title' => 'Rollen',
            'description' => 'Rollen erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'name' => 'Name',
                    'description' => 'Beschreibung',
                    'permissions' => 'Berechtigungen',
                ],
                'actions' => [
                    'create' => 'Rolle erstellen',
                    'edit' => 'Bearbeiten',
                    'delete' => 'Löschen',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Rollen',
            'description' => 'Rolle erstellen / bearbeiten',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => ' ',
                ],
                'description' => [
                    'label' => 'Beschreibung',
                    'hint' => ' ',
                ],
                'permissions' => [
                    'label' => 'Berechtigungen',
                    'hint' => ' ',
                ],
            ],
            'no_group' => 'Weitere Berechtigungen',
        ],
    ],
    'permissions' => [
        'index' => [
            'title' => 'Berechtigungen',
            'description' => 'Berechtigungen bearbeiten',
            'table' => [
                'header' => [
                    'name' => 'Name',
                    'description' => 'Beschreibung',
                ],
                'actions' => [
                    'edit' => 'Bearbeiten',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Berechtigungen',
            'description' => 'Berechtigung bearbeiten',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => ' ',
                ],
                'description' => [
                    'label' => 'Beschreibung',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'permission_groups' => [
        'index' => [
            'title' => 'Berechtigungsgruppen',
            'description' => 'Berechtigungsgruppen bearbeiten',
            'table' => [
                'header' => [
                    'name' => 'Name',
                    'description' => 'Beschreibung',
                ],
                'actions' => [
                    'edit' => 'Bearbeiten',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Berechtigungsgruppen',
            'description' => 'Berechtigungsgruppe bearbeiten',
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'hint' => ' ',
                ],
                'description' => [
                    'label' => 'Beschreibung',
                    'hint' => ' ',
                ],
            ],
        ],
    ],
    'mails' => [
        'mail_types' => [
            'happening_created' => 'Event erstellt',
            'happening_created_with_verification' => 'Event mit Vormerkung erstellt',
            'happening_updated' => 'Event geändert',
            'happening_deleted' => 'Event gelöscht',
            'happening_verified' => 'Event bestätigt',
            'closing_created' => 'Schließzeit erstellt',
            'closing_updated' => 'Schließzeit geändert',
            'closing_deleted' => 'Schließzeit storniert',
        ],
        'index' => [
            'title' => 'E-Mails für Einrichtung ":title"',
            'description' => 'E-Mail-Texte bearbeiten',
            'table' => [
                'header' => [
                    'mail_type' => 'Typ',
                    'subject' => 'Betreff',
                    'is_active' => 'Aktiv?',
                ],
                'actions' => [
                    'create' => 'Mail erstellen',
                    'edit' => 'Bearbeiten',
                    'delete' => 'Löschen',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Mails',
            'description' => 'Mails erstellen / bearbeiten',
            'fields' => [
                'mail_type' => [
                    'label' => 'Typ',
                    'hint' => 'Wählen Sie den Typ der Mail aus.',
                ],
                'subject' => [
                    'label' => 'Betreff',
                    'placeholder' => 'Der Betreff der Mail',
                    'hint' => 'Der Betreff der Mail.',
                ],
                'title' => [
                    'label' => 'Titel',
                    'placeholder' => 'Der Titel der Mail',
                    'hint' => 'Der Titel der Mail.',
                ],
                'salutation' => [
                    'label' => 'Begrüßung',
                    'placeholder' => 'Begrüßungstext der Mail',
                    'hint' => 'Der Begrüßungstext der Mail.',
                ],
                'intro' => [
                    'label' => 'Beginn',
                    'placeholder' => 'Der Start der Mail',
                    'hint' => 'Der Start der Mail. Markdown ist erlaubt.',
                ],
                'outro' => [
                    'label' => 'Schluss',
                    'placeholder' => 'Der Schluss der Mail',
                    'hint' => 'Der Schluss der Mail. Markdown ist erlaubt.',
                ],
                'action_uri' => [
                    'label' => 'Action Button URI',
                    'placeholder' => 'URI hinter Action Button',
                    'hint' => 'Eine Action URI, die hinter einem Button liegt.',
                ],
                'action_uri_label' => [
                    'label' => 'Action Button URI Label',
                    'placeholder' => 'Label für Action Button',
                    'hint' => 'Ein Button-Label für die Action URI.',
                ],
                'farewell' => [
                    'label' => 'Verabschiedung',
                    'placeholder' => 'Verabschiedungstext der Mail',
                    'hint' => 'Der Verabschiedungstext der Mail. Markdown ist erlaubt.',
                ],
                'is_active' => [
                    'label' => 'Aktiv',
                    'hint' => 'Ist die Mail aktiv?',
                ],
            ],
        ],
    ],
    'user_groups' => [
        'index' => [
            'title' => 'Benutzergruppen',
            'description' => 'Benutzergruppen erstellen, bearbeiten, löschen',
            'table' => [
                'header' => [
                    'title' => 'Titel',
                    'institution' => 'Einrichtung',
                ],
                'actions' => [
                    'create' => 'Benutzergruppe erstellen',
                    'import' => 'Import',
                    'edit' => 'Bearbeiten',
                    'delete' => 'Löschen',
                    'users' => 'Benutzer',
                ],
            ],
        ],
        'form' => [
            'title' => 'Formular für Benutzergruppen',
            'description' => 'Benutzergruppe erstellen / bearbeiten',
            'fields' => [
                'institution' => [
                    'label' => 'Einrichtung',
                    'placeholder' => 'Einrichtung',
                    'hint' => ' ',
                ],
                'title' => [
                    'label' => 'Titel',
                    'placeholder' => 'Titel',
                    'hint' => ' ',
                ],
            ],
        ],
        'import' => [
            'title' => 'Benutzer-Import',
            'description' => 'Benutzer zu Benutzergruppe :title hinzufügen',
            'fields' => [
                'users' => [
                    'label' => 'Benutzer',
                    'hint' => 'Ein Benutzername pro Zeile.',
                ],
                'valid_from' => [
                    'label' => 'Gültig ab',
                    'hint' => ' ',
                ],
                'valid_until' => [
                    'label' => 'Gültig bis',
                    'hint' => ' ',
                ],
                'units' => [
                    'days' => 'Tag(e)',
                    'weeks' => 'Woche(n)',
                    'months' => 'Monat(e)',
                    'years' => 'Jahr(e)',
                ]
            ],
        ],
        'users' => [
            'title' => 'Benutzer der Benutzergruppe :title',
            'table' => [
                'header' => [
                    'name' => 'Name',
                    'email' => 'E-Mail',
                    'valid_from' => 'Gültig ab',
                    'valid_until' => 'Gültig bis',
                ],
                'actions' => [
                    'remove' => 'Entfernen',
                ],
            ]
        ]
    ],
];

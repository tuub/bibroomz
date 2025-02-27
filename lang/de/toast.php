<?php

return [
    'no_auth' => 'Sie müssen sich anmelden, um Reservierungen vornehmen zu können.',
    'login' => [
        'success' => 'Login erfolgreich',
        'error' => 'Loginversuch fehlgeschlagen',
    ],
    'logout' => [
        'success' => 'Logout erfolgreich',
        'error' => 'Logout fehlgeschlagen',
    ],
    'happening' => [
        'event' => [
            'created' => 'Buchung erstellt',
            'updated' => 'Buchung aktualisiert',
            'verified' => 'Buchung bestätigt',
            'deleted' => 'Buchung gelöscht',
            'scheduler' => 'Unbestätigte Buchung gelöscht',
        ],
        'info' => [
            'date' => 'Datum',
            'start' => 'Beginn',
            'end' => 'Ende',
        ],
    ],
    'quota' => [
        'happening_block_hours' => 'Buchungen können höchstens :limit Stunden dauern!',
        'weekly_happenings' => 'Wöchentliches Buchungskontingent überschritten: :remaining verbleibend',
        'weekly_hours' => 'Wöchentliches Stundenkontingent überschritten: :remaining Stunden verbleibend',
        'daily_hours' => 'Tägliches Stundenkontingent überschritten: :remaining Stunden verbleibend',
    ],
    'concurrent_happening' => 'Es ist nur eine Buchung zur gleichen Zeit möglich!',
    'wrong_user_group' => ':Resource_type :resource_title ist für Ihre Nutzer:innengruppe nicht verfügbar.'
];

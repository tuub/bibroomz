<?php

declare(strict_types=1);

return [
    'errors' => [
        'no_auth' => 'Nicht eingeloggt.',
        'no_email' => 'Einloggen fehlgeschlagen: Ihrem Bibliothekskonto ist keine E-Mail-Adresse hinterlegt.',
        'user_not_found' => 'Einloggen fehlgeschlagen (Überprüfen Sie Ihr Passwort und Bibliothekskonto).',
    ],
    'failed' => 'Diese Kombination aus Zugangsdaten wurde nicht in unserer Datenbank gefunden.',
    'login' => [
        'error' => 'Loginversuch fehlgeschlagen.',
        'success' => 'Erfolgreich eingeloggt.',
    ],
    'logout' => [
        'error' => 'Logoutversuch fehlgeschlagen.',
        'success' => 'Erfolgreich ausgeloggt.',
    ],
    'password' => 'Das Passwort ist falsch.',
    'throttle' => 'Zu viele Loginversuche. Versuchen Sie es bitte in :seconds Sekunden nochmal.',
];

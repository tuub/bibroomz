<?php

return [
    'header' => 'Anmelden',
    'description' => 'Studierende der TU Berlin melden sich mit ihrem TUB-Account an. UdK-Studierende melden sich mit ihrer Nummer auf der Campuscard und dem Bibliothekspasswort an.',
    'form' => [
        'username' => [
            'label' => 'Bibliothekskonto (TU: TUB-Account) ',
            'placeholder' => 'Ihr Bibliothekskonto (TU: TUB-Account) ',
            'hint' => 'Über Ihren Benutzernamen',
        ],
        'password' => [
            'label' => 'Passwort',
            'placeholder' => 'Ihr Passwort',
            'hint' => 'Über Ihr Passwort',
        ],
        'submit' => [
            'label' => 'Login',
        ],
    ],
];

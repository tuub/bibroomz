<?php

return [
    'header' => 'Login',
    'description' => 'TU Berlin students log in with their TUB account. UdK students log in with their campus card number and library account password.',
    'form' => [
        'username' => [
            'label' => 'Library Account (TU: TUB Account)',
            'placeholder' => 'Your Library Account (TU: TUB Account)',
            'hint' => 'About your username',
        ],
        'password' => [
            'label' => 'Password',
            'placeholder' => 'Your password',
            'hint' => 'About your password',
        ],
        'submit' => [
            'label' => 'Login',
        ],
    ],
];

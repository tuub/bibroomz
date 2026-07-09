<?php

declare(strict_types=1);

return [
    'errors' => [
        'no_auth' => 'Not logged in.',
        'no_email' => 'Login failed: your library account has no email address registered.',
        'user_not_found' => 'Login failed (check your password and library account).',
    ],
    'failed' => 'These credentials do not match our records.',
    'login' => [
        'error' => 'Failed to login.',
        'success' => 'Successfully logged in.',
    ],
    'logout' => [
        'error' => 'Failed to logout.',
        'success' => 'Successfully logged out.',
    ],
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
];

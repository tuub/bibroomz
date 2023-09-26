<?php

return [
    'no_auth' => 'You need to login in order to create bookings',
    'login' => [
        'success' => 'Login successful',
        'error' => 'Login attempt failed',
    ],
    'logout' => [
        'success' => 'Logout successful',
        'error' => 'Logout failed',
    ],
    'happening' => [
        'event' => [
            'created' => 'Booking created:',
            'updated' => 'Booking updated:',
            'verified' => 'Booking verified:',
            'deleted' => 'Booking deleted:',
            'scheduler' => 'Unverified booking deleted after 1 hour:',
        ],
        'info' => [
            'date' => 'Date',
            'start' => 'Start',
            'end' => 'End',
        ],
    ],
    'quota' => [
        'happening_block_hours' => 'Bookings can only be booked for :limit hours!',
        'weekly_happenings' => 'Weekly events quota limit exceeded: :remaining remaining',
        'weekly_hours' => 'Weekly hours quota limit exceeded: :remaining hours remaining',
        'daily_hours' => 'Daily hours quota limit exceeded: :remaining hours remaining',
    ],
    'concurrent_happening' => 'Only one booking at a time is allowed!',
];

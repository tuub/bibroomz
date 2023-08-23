<?php

return [
    'no_auth' => 'You need to login in order to create bookings',
    'login' => [
        'success' => 'Login successfull',
        'error' => 'Login attempt failed',
    ],
    'logout' => [
        'success' => 'Logout successfull',
        'error' => 'Logout failed',
    ],
    'happening' => [
        'event' => [
            'created' => 'Event created:',
            'updated' => 'Event updated:',
            'verified' => 'Event verified:',
            'deleted' => 'Event deleted:',
            'scheduler' => 'Unverified event deleted after 1 hour:',
        ],
        'info' => [
            'date' => 'Date',
            'start' => 'Start',
            'end' => 'End',
        ],
    ],
    'quota' => [
        'happening_block_hours' => 'Events can only be booked for :limit hours!',
        'weekly_happenings' => 'Weekly events quota limit exceeded: :remaining remaining',
        'weekly_hours' => 'Weekly hours quota limit exceeded: :remaining hours remaining',
        'daily_hours' => 'Daily hours quota limit exceeded: :remaining hours remaining',
    ]
];

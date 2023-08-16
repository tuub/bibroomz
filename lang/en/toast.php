<?php

return [
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
            'created' => 'Event created!',
            'updated' => 'Event updated!',
            'verified' => 'Event verified!',
            'deleted' => 'Event deleted!',
        ],
        'info' => [
            'date' => 'Date',
            'start' => 'Start',
            'end' => 'End',
        ],
    ],
    'quota' => [
        'happening_block_hours' => 'Block hours quota limit exceeded: :current of :limit',
        'weekly_happenings' => 'Weekly events quota limit exceeded: :current of :limit',
        'weekly_hours' => 'Weekly hours quota limit exceeded: :current of :limit',
        'daily_hours' => 'Daily hours quota limit exceeded: :current of :limit',
    ]
];

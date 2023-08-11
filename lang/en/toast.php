<?php

return [
    'login' => [
        'success' => 'Login successfull: :message',
        'error' => 'Login attempt failed: :message',
    ],
    'logout' => [
        'success' => 'Logout successfull: :message',
        'error' => 'Logout failed: :message',
    ],
    'happening' => [
        'created' => 'Event created! :message',
        'updated' => 'Event updated! :message',
        'verified' => 'Event verified! :message',
        'deleted' => 'Event deleted! :message',
    ],
    'quota' => [
        'happening_block_hours' => 'Block hours quota limit exceeded: :current of :limit',
        'weekly_happenings' => 'Weekly events quota limit exceeded: :current of :limit',
        'weekly_hours' => 'Weekly hours quota limit exceeded: :current of :limit',
        'daily_hours' => 'Daily hours quota limit exceeded: :current of :limit',
    ]
];

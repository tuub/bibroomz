<?php

return [
    'app' => [
        'name' => env('APP_NAME', 'Roomz'),
        'version' => env('APP_VERSION', '1.0'),
        'locale' => env('APP_LOCALE', 'de'),
        'scheme' => env('APP_SCHEME', 'http'),
        'host' => env('APP_HOST', 'roomz.localhost'),
        'port' => env('APP_PORT', 80),
        'url' => env('APP_URL', 'http://roomz.localhost:80'),
        'timezone' => env('APP_TIMEZONE', 'Europe/Berlin'),
    ],
    'log' => [
        'level' => env('LOG_LEVEL', 'warning')
    ],
    'auth' => [
        'method' => env('AUTH_METHOD', 'alma'),
        'api' => [
            'endpoint' => env('AUTH_API_ENDPOINT', 'http://my.api.org'),
            'timeout' => env('AUTH_API_TIMEOUT', 15),
            'is_debug' => env('AUTH_API_DEBUG', false),
            'log_file' => env('AUTH_API_STORAGE_LOG_FILE', 'curl.log'),
        ],
    ],
    'database' => [
        'is_seed_example_institution' => env('DB_SEED_EXAMPLE_INSTITUTION', false),
        'is_seed_tub_institutions' => env('DB_SEED_TUB_INSTITUTIONS', false),
    ],
    'default' => [
        'locale' => env('DEFAULT_LOCALE', 'de'),
        'timezone' => env('DEFAULT_TIMEZONE', 'Europe/Berlin'),
        'start_time_slot' => env('DEFAULT_START_TIME_SLOT', '09:00'),
        'end_time_slot' => env('DEFAULT_END_TIME_SLOT', '22:00'),
        'quota' => [
            'weekly_happenings' => env('DEFAULT_QUOTA_WEEKLY_HAPPENINGS', 5),
            'daily_hours' => env('DEFAULT_QUOTA_DAILY_HOURS', 4),
            'weekly_hours' => env('DEFAULT_QUOTA_WEEKLY_HOURS', 12),
            'happening_block_hours' => env('DEFAULT_QUOTA_HAPPENING_BLOCK_HOURS', 4),
        ],
        'date_format' => env('DEFAULT_DATE_FORMAT', "DD.MM.YYYY"),
        'time_format' => env('DEFAULT_TIME_FORMAT', 'H:i'),
        'timeslot_length' => env('DEFAULT_TIMESLOT_LENGTH', '00:30'),
        'weeks_in_advance' => env('DEFAULT_WEEKS_IN_ADVANCE', 2),
        'cleanup_interval' => env('DEFAULT_CLEANUP_INTERVAL', ':01:00'),
        'is_label_enabled' => env('DEFAULT_IS_LABEL_ENABLED', false),
    ],
    'user' => [
        'is_suspension_enabled' => env('USER_SUSPENSION_ENABLED', true),
        'suspension_days' => env('USER_SUSPENSION_DAYS', 3),
        'cleanup_days' => env('USER_CLEANUP_DAYS', 30),
    ],
    'happenings' => [
        'cleanup_days' => env('HAPPENING_CLEANUP_DAYS', 30),
    ],
    'test-accounts' => [
        'is_enabled' => env('IS_TEST_ACCOUNTS_ENABLED', false),
        'admin' => [
            'username' => env('ADMIN_USER', 'admin'),
            'password' => env('ADMIN_PASSWORD', 'admin'),
            'email' => env('ADMIN_EMAIL', 'admin@example.org'),
        ],
        'test1' => [
            'username' => env('TEST_USER_01', 'test1'),
            'password' => env('TEST_USER_01_PASSWORD', 'test1'),
            'email' => env('TEST_USER_01_EMAIL', 'test1@example.org'),
        ],
        'test2' => [
            'username' => env('TEST_USER_02', 'test2'),
            'password' => env('TEST_USER_02_PASSWORD', 'test2'),
            'email' => env('TEST_USER_02_EMAIL', 'test2@example.org'),
        ]
    ]
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'business_hours' => [
        // MONDAY
        1 => [
            'start' => '09:00',
            'end' => '22:00',
        ],
        // TUESDAY
        2 => [
            'start' => '09:00',
            'end' => '22:00',
        ],
        // WEDNESDAY
        3 => [
            'start' => '09:00',
            'end' => '22:00',
        ],
        // THURSDAY
        4 => [
            'start' => '09:00',
            'end' => '22:00',
        ],
        // FRIDAY
        5 => [
            'start' => '09:00',
            'end' => '22:00',
        ],
        // SATURDAY
        6 => [
            'start' => '10:00',
            'end' => '18:00',
        ],
        // SUNDAY
        0 => [
            'start' => null,
            'end' => null,
        ]
    ],
];

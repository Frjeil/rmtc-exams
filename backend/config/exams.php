<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Voto: range accettato
    |--------------------------------------------------------------------------
    */
    'vote_min' => (int) env('VOTE_MIN', 18),
    'vote_max' => (int) env('VOTE_MAX', 30),

    /*
    |--------------------------------------------------------------------------
    | Nager.Date (API giorni festivi)
    |--------------------------------------------------------------------------
    */
    'nager' => [
        'base_url' => env('NAGER_BASE_URL', 'https://date.nager.at/api/v3'),
        'country_code' => env('NAGER_COUNTRY_CODE', 'IT'),
        'timeout' => (int) env('NAGER_TIMEOUT', 3),
    ],
];

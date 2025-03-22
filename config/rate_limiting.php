<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | This file contains the rate limiting configuration for the API routes.
    | Different endpoints have different rate limits based on their sensitivity
    | and resource consumption.
    |
    */

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | Standard API Rate Limiting
        |--------------------------------------------------------------------------
        |
        | For general API endpoints like listing and searching
        |
        */
        'standard' => [
            'attempts' => 60,     // 60 requests
            'decay' => 60,        // per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Profile-related API Rate Limiting
        |--------------------------------------------------------------------------
        |
        | For profile-related endpoints that may be more commonly used
        |
        */
        'profile' => [
            'attempts' => 30,     // 30 requests
            'decay' => 60,        // per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Critical API Rate Limiting
        |--------------------------------------------------------------------------
        |
        | For sensitive operations like preference changes and updates
        |
        */
        'critical' => [
            'attempts' => 10,     // 10 requests
            'decay' => 60,        // per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Auth API Rate Limiting
        |--------------------------------------------------------------------------
        |
        | For authentication-related endpoints to prevent brute force
        |
        */
        'auth' => [
            'attempts' => 5,      // 5 requests
            'decay' => 60,        // per minute
        ],
    ],
];

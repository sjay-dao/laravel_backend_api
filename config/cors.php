<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') ? array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS'))))) : [
        'http://localhost:5173',
        'http://localhost:5073',
        'http://127.0.0.1:5173',
        'http://localhost:5175',
        'http://127.0.0.1:5175',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'http://localhost:50889',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];

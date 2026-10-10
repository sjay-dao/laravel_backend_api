<?php

return [
    'enabled' => env('DEMO_MODE', false) === true,
    'database' => env('DEMO_DATABASE'),
    'date' => env('DEMO_ANCHOR_DATE', '2026-10-02'),
    'password' => 'MicaDemo2026!',
];

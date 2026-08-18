<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Delivery Management Configuration
    |--------------------------------------------------------------------------
    |
    | Policy: Version 1 specifies 3 max delivery attempts per assignment by default.
    | Configurable via DELIVERY_MAX_ATTEMPTS environment variable.
    | When attempt_count reaches max_attempts, status transitions to 'delivery_failed'
    | and requires supervisor approval before returning to central warehouse (hayest_central).
    |
    */
    'enabled' => env('DELIVERY_MODULE_ENABLED', true),
    'default_currency' => env('DELIVERY_DEFAULT_CURRENCY', null),
    'max_delivery_attempts' => (int) env('DELIVERY_MAX_ATTEMPTS', 3),
    'central_warehouse_code' => 'hayest_central',
];

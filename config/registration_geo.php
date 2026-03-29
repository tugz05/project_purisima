<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tago, Surigao del Sur — registration bounding box (WGS84)
    |--------------------------------------------------------------------------
    |
    | Approximate municipality bounds for verifying new resident sign-ups.
    | Tune with REGISTRATION_TAGO_* env vars if GPS checks are too strict or loose.
    |
    */
    'tago_bounds' => [
        'min_lat' => (float) env('REGISTRATION_TAGO_MIN_LAT', 8.88),
        'max_lat' => (float) env('REGISTRATION_TAGO_MAX_LAT', 9.16),
        'min_lng' => (float) env('REGISTRATION_TAGO_MIN_LNG', 126.10),
        'max_lng' => (float) env('REGISTRATION_TAGO_MAX_LNG', 126.38),
    ],

];

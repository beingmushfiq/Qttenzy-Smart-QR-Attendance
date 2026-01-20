<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Face Recognition Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum face match score (0-100) required for verification
    |
    */
    'face_match_threshold' => env('FACE_MATCH_THRESHOLD', 70.0),

    /*
    |--------------------------------------------------------------------------
    | Default Location Radius
    |--------------------------------------------------------------------------
    |
    | Default radius in meters for location validation
    |
    */
    'default_radius_meters' => env('DEFAULT_RADIUS_METERS', 100),

    /*
    |--------------------------------------------------------------------------
    | QR Code Rotation Interval
    |--------------------------------------------------------------------------
    |
    | QR code rotation interval in seconds (default: 5 minutes)
    |
    */
    'qr_rotation_interval' => env('QR_ROTATION_INTERVAL', 300),
];


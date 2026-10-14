<?php

return [
    'api_key' => env('SENDKUY_API_KEY'),
    'base_url' => env('SENDKUY_BASE_URL'),
    'timeout' => env('SENDKUY_TIMEOUT', 30),
    'max_retries' => env('SENDKUY_MAX_RETRIES', 2),
    'webhook' => [
        'secret' => env('SENDKUY_WEBHOOK_SECRET'),
        'path' => env('SENDKUY_WEBHOOK_PATH', 'sendkuy/webhook'),
        'tolerance' => env('SENDKUY_WEBHOOK_TOLERANCE', 300),
    ],
];

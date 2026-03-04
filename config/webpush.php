<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID keys
    |--------------------------------------------------------------------------
    |
    | Public and private VAPID keys used to authenticate Web Push messages.
    | You can generate them with the package command or any VAPID tool.
    |
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public_key' => env('VAPID_PUBLIC_KEY', null),
        'private_key' => env('VAPID_PRIVATE_KEY', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | GCM / FCM API key (optional)
    |--------------------------------------------------------------------------
    */
    'gcm' => [
        'api_key' => env('GCM_API_KEY', null),
    ],
];

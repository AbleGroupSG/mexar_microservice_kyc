<?php

return [
    'url' => env('SIJITU_BASE_URL', 'https://sandbox-api.espay.id'),
    'authorization' => env('SIJITU_AUTHORIZATION'),
    'username' => env('SIJITU_USERNAME'),
    'password' => env('SIJITU_PASSWORD'),
    'sender_id' => env('SIJITU_SENDER_ID'),
    'user_id' => env('SIJITU_USER_ID'),
    'organization_id' => env('SIJITU_ORGANIZATION_ID'),
    'signature_key' => env('SIJITU_SIGNATURE_KEY'),
];

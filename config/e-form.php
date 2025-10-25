<?php 


return [
    'regtank' => [
        'specific_server_url' => env('COMPANY_SPECIFIC_REGTANK_SERVICE_URL', 'https://api.regtank.com'),
        'api_key' => env('REGTANK_API_KEY', ''),
    ],
];
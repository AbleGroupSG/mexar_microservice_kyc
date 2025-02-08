<?php

return [
    'server_url' => env('REGTANK_CRM_SERVER_URL', 'https://crm-server.regtank.com'),
    'id_template' => env('CLIENT_ID_TEMPLATE'),
    'secret_template' => env('CLIENT_SECRET_TEMPLATE'),
    'specific_server_url'=>env('COMPANY_SPECIFIC_REGTANK_SERVICE_URL'),
    'assignee' => env('REGTANK_ASIGNEE')
];

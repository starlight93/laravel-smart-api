<?php

return [
    'user_table' => env('API_USER_TABLE', 'default_users'),
    'user_active_when' => env('API_USER_ACTIVE_WHEN', ''), // API_USER_ACTIVE_WHEN=status:active
    'route_prefix' => env('API_ROUTE_PREFIX', 'api'),
    'provider' => env('API_PROVIDER', '')
];
<?php

return [
    'user_table' => env('API_USER_TABLE', 'default_users'),
    'user_active_when' => env('API_USER_ACTIVE_WHEN', ''), // API_USER_ACTIVE_WHEN=status:active
    'route_prefix' => env('API_ROUTE_PREFIX', 'api'),
    'provider' => env('API_PROVIDER', ''),

    'auth' => [
        /*
         * Driver token yang dipakai endpoint package: jwt | sanctum | passport.
         * - jwt      : package membawa tymon/jwt-auth sendiri (perilaku lama, default).
         * - sanctum  : pakai laravel/sanctum milik aplikasi host.
         * - passport : pakai laravel/passport milik aplikasi host.
         */
        'driver' => env('API_AUTH_DRIVER', 'jwt'),

        /*
         * Guard yang dipakai untuk resolve user. Kosong = otomatis:
         * jwt -> 'api', sanctum -> 'sanctum', passport -> 'api'.
         */
        'guard' => env('API_AUTH_GUARD', ''),

        /*
         * Timpa auth.guards / auth.providers / auth.defaults aplikasi host dengan
         * milik package (guard jwt + model User package). Default hanya aktif saat
         * driver = jwt; untuk sanctum/passport config auth host dibiarkan apa adanya.
         */
        'override_config' => env('API_AUTH_OVERRIDE_CONFIG', null),

        // Nama token yang dibuat saat login (sanctum/passport).
        'token_name' => env('API_AUTH_TOKEN_NAME', 'api'),
    ],
];

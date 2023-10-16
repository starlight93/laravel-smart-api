<?php

return [
    'password' => env('EDITOR_PASSWORD', '12345'),
    'frontend_devs' => env('EDITOR_FRONTENDERS', ''), // 'frontend-dev-1,frontend-dev-2'
    'backend_devs' => env('EDITOR_BACKENDERS', ''), // 'dev-1,dev-2'
    'owners' => env('EDITOR_OWNERS', 'dev-owner'), // 'owner1, owner2'
];
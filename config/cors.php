<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_merge(
        explode(',', env('FRONTEND_URL', 'http://localhost:5173')),
        ['http://localhost:5173', 'http://localhost:5174']
    )),

    'allowed_origins_patterns' => [
        '/https:\/\/.*\.vercel\.app$/',  // Tous les domaines Vercel
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];

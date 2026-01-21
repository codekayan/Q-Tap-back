<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // For cookie-based auth (qutap_auth) you MUST:
    // - set supports_credentials => true
    // - NOT use allowed_origins => ['*']
    //
    // Hardcoded allow-list (no env).
    // NOTE: When supports_credentials=true you cannot use '*'.
    'allowed_origins' => [
        'https://qutap.co',
        'https://www.qutap.co',
        'https://dashboard.qutap.co',
        'https://panel.qutap.co',
        // If you call your API from the browser directly (rare), keep this:
        'https://api.qutap.co',
    ],

    // Keep empty; we’re using an explicit allow-list above.
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

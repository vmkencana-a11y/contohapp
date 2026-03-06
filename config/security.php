<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auth Cookie Settings (Custom Token Sessions)
    |--------------------------------------------------------------------------
    |
    | This project uses custom token-based sessions stored in DB (user_sessions,
    | admin_sessions) and delivered to the client via cookies.
    |
    | IMPORTANT: Do not couple these cookie attributes to Laravel's own session
    | cookie settings, because Laravel's session cookie may need SameSite=Lax
    | for OAuth redirects while auth cookies can remain stricter.
    |
    */
    'auth_cookie' => [
        'domain' => env('SESSION_DOMAIN'),
        'path' => env('SESSION_PATH', '/'),
        'secure' => env('AUTH_COOKIE_SECURE', env('SESSION_SECURE_COOKIE', true)),
        'http_only' => true,
        'same_site' => env('AUTH_COOKIE_SAME_SITE', 'strict'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Rotation
    |--------------------------------------------------------------------------
    |
    | Token rotation reduces the useful lifetime of a leaked token. Rotation is
    | only enabled for cookie-based auth, since we can deliver the new token in
    | Set-Cookie. Bearer-token rotation is intentionally not auto-enabled.
    |
    */
    'token_rotation_enabled' => env('AUTH_TOKEN_ROTATION_ENABLED', true),
    'token_rotation_hours' => (int) env('AUTH_TOKEN_ROTATION_HOURS', 6),
];


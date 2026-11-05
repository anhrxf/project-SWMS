<?php

$origins = array_filter(array_map(
    'trim',
    explode(',', (string) env(
        'SWMS_CORS_ALLOWED_ORIGINS',
        'http://localhost:5000,http://127.0.0.1:5000,http://localhost:8080,http://127.0.0.1:8080'
    ))
));

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_values($origins),
    'allowed_origins_patterns' => ['#^https?://.*\.trycloudflare\.com$#'],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];

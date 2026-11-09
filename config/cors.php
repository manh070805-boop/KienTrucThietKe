<?php

return [
    'paths' => ['api/*'], 'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/')],
    'allowed_origins_patterns' => [], 'allowed_headers' => ['Accept', 'Content-Type', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN'],
    'exposed_headers' => [], 'max_age' => 0, 'supports_credentials' => true,
];

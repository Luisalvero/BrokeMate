<?php
return [
    'app_name' => 'BrokeMate',
    'env' => getenv('APP_ENV') ?: 'local',
    'base_url' => getenv('APP_URL') ?: 'http://localhost:8080',
    'db_path' => __DIR__ . '/../database/app.db',
    'session_name' => 'bm_session',
    'csrf_key' => 'bm_csrf_token',
    'rate_limit_dir' => __DIR__ . '/../tmp/ratelimit',
    'default_currency' => 'USD',
];

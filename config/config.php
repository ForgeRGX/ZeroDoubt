<?php
declare(strict_types=1);

return [
    'debug'    => env('APP_DEBUG', 'false') === 'true',
    'env'      => env('APP_ENV', 'production'),
    'per_page' => 20,
    'allow_registration' => env('ALLOW_REGISTRATION', 'true') === 'true',
    'db' => [
        'host' => env('DB_HOST', 'db'),
        'port' => (int) env('DB_PORT', '3306'),
        'name' => env('MYSQL_DATABASE'),
        'user' => env('MYSQL_USER'),
        'pass' => env('MYSQL_PASSWORD'),
    ],
];

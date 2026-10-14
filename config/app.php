<?php
declare(strict_types=1);

use src\Core\Env;

return [
    'name' => (string) Env::get (
        'APP_NAME',
        'Study Roadmap API'
    ),

    'env' => (string) Env::get (
        'APP_ENV',
        'production'
    ),

    'debug' => Env::bool (
        'APP_DEBUG',
        false
    ),

    'timezone' => (string) Env::get (
        'APP_TIMEZONE',
        'Asia/Ho_Chi_Minh'
    ),

    'cors_origin' => (string) Env::get (
        'CORS_ALLOWED_ORIGIN',
        '*'
    ),

    'jwt' => [
        'secret' => (string) Env::get (
            'JWT_SECRET',
            ''
        ),

        'ttl' => (int) Env::get (
            'JWT_TTL',
            86400
        ),
    ],
];
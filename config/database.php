<?php
declare(strict_types=1);

use src\Core\Env;

return [
    'host' => (string) Env::get (
        'DB_HOST',
        '127.0.0.1'
    ),

    'port' => (int) Env::get (
        'DB_PORT',
        3306
    ),
    
    'name' => (string) Env::get (
        'DB_NAME',
        ''
    ),

    'user' => (string) Env::get (
        'DB_USER',
        'root'
    ),

    'pass' => (string) Env::get (
        'DB_PASSWORD',
        ''
    ),

    'charset' => (string) Env::get (
        'DB_CHARSET',
        'utf8mb4'
    ),

    'timezone' => (string) Env::get (
        'DB_TIMEZONE',
        '+07:00'
    ),
];
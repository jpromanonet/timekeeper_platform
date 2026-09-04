<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'host' => tk_env('DB_HOST', '127.0.0.1'),
    'port' => (int) tk_env('DB_PORT', '3306'),
    'name' => tk_env('DB_NAME', 'timekeeper'),
    'user' => tk_env('DB_USER', 'root'),
    'pass' => tk_env('DB_PASS', 'local_admin') ?? 'local_admin',
    'charset' => 'utf8mb4',
];

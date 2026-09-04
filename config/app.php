<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'name' => tk_env('APP_NAME', 'Timekeeper'),
    'tagline' => 'Colección personal',
    'env' => strtolower((string) tk_env('APP_ENV', 'local')),
    'debug' => filter_var(tk_env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'url' => '',
    'session_name' => (string) tk_env('SESSION_NAME', 'timekeeper_session'),
    'session_lifetime' => (int) tk_env('SESSION_LIFETIME', '28800'),
    'session_idle' => (int) tk_env('SESSION_IDLE', '7200'),
    'timezone' => 'America/Argentina/Buenos_Aires',
    'locale' => 'es_AR',
    'version' => '1.0.0',
];

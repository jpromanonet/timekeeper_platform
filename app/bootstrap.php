<?php

declare(strict_types=1);

$appConfig = require dirname(__DIR__) . '/config/app.php';
$dbConfig = require dirname(__DIR__) . '/config/database.php';

date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Services/DatePrecision.php';
require_once __DIR__ . '/Services/UploadService.php';
require_once __DIR__ . '/Services/PreferenceService.php';
require_once __DIR__ . '/Services/CollectionService.php';
require_once __DIR__ . '/Services/TimelineService.php';
require_once __DIR__ . '/Services/CategoryService.php';
require_once __DIR__ . '/Services/TagService.php';
require_once __DIR__ . '/Services/EventService.php';
require_once __DIR__ . '/Services/SearchService.php';
require_once __DIR__ . '/Services/ExportService.php';
require_once __DIR__ . '/Services/PdfWriter.php';
require_once __DIR__ . '/Services/TimelinePdfService.php';
require_once __DIR__ . '/Services/StatsService.php';
require_once __DIR__ . '/Services/SeedService.php';

foreach ([
    'AuthController',
    'DashboardController',
    'CollectionController',
    'TimelineController',
    'EventController',
    'SearchController',
    'SettingsController',
    'TrashController',
    'FavoritesController',
    'MetricsController',
] as $controller) {
    require_once __DIR__ . '/Controllers/' . $controller . '.php';
}

if (PHP_SAPI !== 'cli') {
    Auth::startSession($appConfig['session_name']);
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

try {
    Database::connect($dbConfig);
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'DB error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    $installUrl = (function_exists('base_path') ? base_path() : '') . '/install.php';
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Timekeeper</title></head>';
    echo '<body style="font-family:IBM Plex Mono,monospace;background:#181821;color:#E1DED5;padding:2rem">';
    echo '<h1>TIMEKEEPER</h1>';
    echo '<p>El archivo todavía no está instalado.</p>';
    echo '<p><a href="' . htmlspecialchars($installUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#91A7C4">Abrir install.php</a></p>';
    if (!empty($appConfig['debug'])) {
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '</body></html>';
    exit;
}

return [
    'app' => $appConfig,
    'db' => $dbConfig,
];

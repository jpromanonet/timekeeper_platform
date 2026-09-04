<?php

declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/Services/DatePrecision.php';
require_once __DIR__ . '/app/Services/PreferenceService.php';
require_once __DIR__ . '/app/Services/CollectionService.php';
require_once __DIR__ . '/app/Services/TimelineService.php';
require_once __DIR__ . '/app/Services/CategoryService.php';
require_once __DIR__ . '/app/Services/TagService.php';
require_once __DIR__ . '/app/Services/EventService.php';
require_once __DIR__ . '/app/Services/SeedService.php';

$appConfig = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';
date_default_timezone_set($appConfig['timezone']);

$messages = [];
$ok = false;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $server = Database::connectServer($dbConfig);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $dbConfig['name']) ?: 'timekeeper';
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $messages[] = "Base `{$dbName}` lista.";

        Database::connect($dbConfig);
        $pdo = Database::pdo();
        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('No se pudo leer sql/schema.sql');
        }
        $parts = explode(';', $schema);
        $applied = 0;
        foreach ($parts as $part) {
            $lines = preg_split("/\r\n|\n|\r/", $part) ?: [];
            $clean = [];
            foreach ($lines as $line) {
                $trim = trim($line);
                if ($trim === '' || str_starts_with($trim, '--')) {
                    continue;
                }
                $clean[] = $line;
            }
            $statement = trim(implode("\n", $clean));
            if ($statement === '') {
                continue;
            }
            $pdo->exec($statement);
            $applied++;
        }
        $messages[] = "Esquema aplicado ({$applied} sentencias).";
        $report = SeedService::run($pdo);
        $messages[] = $report['seeded']
            ? 'Datos de ejemplo cargados.'
            : 'El archivo de ejemplo ya existía.';
        $messages[] = 'Usuario: ' . SeedService::demoEmail() . ' / ' . SeedService::demoPassword();
        $ok = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$already = false;
try {
    Database::connect($dbConfig);
    $chk = Database::pdo()->query("SHOW TABLES LIKE 'users'");
    $already = $chk && $chk->fetch() !== false;
} catch (Throwable) {
    $already = false;
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="archivist">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalar Timekeeper</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Pixelify+Sans:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
<main class="auth-wrap">
    <div class="auth-mark">
        <h1>TIMEKEEPER</h1>
        <p>INSTALACIÓN</p>
    </div>
    <div class="panel auth-panel">
        <p>Crea la base <strong><?= e((string) $dbConfig['name']) ?></strong>, aplica el esquema y carga un archivo de ejemplo.</p>
        <?php if ($ok): ?>
            <div class="flash flash-ok">
                <ul>
                    <?php foreach ($messages as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
                </ul>
                <p><a class="btn btn-primary" href="index.php?r=/login">Entrar</a></p>
            </div>
        <?php else: ?>
            <?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
            <?php if ($already): ?><p class="muted">Ya hay tablas. Podés reaplicar el esquema.</p><?php endif; ?>
            <form method="post">
                <button class="btn btn-primary" type="submit"><?= $already ? 'Reaplicar' : 'Instalar ahora' ?></button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>

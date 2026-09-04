<?php
/** @var string $templateFile */
/** @var string $appName */
/** @var string|null $title */
$tagline = (string) app_config('tagline', 'Colección personal');
?>
<!DOCTYPE html>
<html lang="es" data-theme="archivist">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Entrar') . ' · ' . $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Pixelify+Sans:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/app.css') ?>">
    <link rel="icon" href="<?= e(url('/assets/icons/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="auth-body">
<main class="auth-wrap">
    <div class="auth-mark">
        <?= icon('hourglass', 36) ?>
        <h1>TIMEKEEPER</h1>
        <p><?= e(mb_strtoupper($tagline)) ?></p>
    </div>
    <div class="panel auth-panel">
        <?php require $templateFile; ?>
    </div>
</main>
</body>
</html>

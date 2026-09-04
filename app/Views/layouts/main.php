<?php
/** @var string $templateFile */
/** @var string $appName */
/** @var array|null $user */
/** @var string|null $currentNav */
/** @var string|null $title */

$prefs = Auth::prefs();
$theme = $prefs['theme'] ?? 'archivist';
$density = $prefs['density'] ?? 'compact';
$success = flash('success');
$error = flash('error');
$nav = $currentNav ?? '';
$palette = Auth::check() ? SearchService::palette(Auth::id()) : ['archives' => [], 'timelines' => []];
$tagline = (string) app_config('tagline', 'Colección personal');
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= e($theme) ?>" data-density="<?= e($density) ?>" data-sounds="<?= !empty($prefs['interface_sounds']) ? '1' : '0' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Archivo') . ' · ' . $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Pixelify+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/app.css') ?>">
    <link rel="icon" href="<?= e(url('/assets/icons/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="app-body" data-app-index="<?= e(base_path() . '/index.php') ?>">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand-mark" aria-hidden="true"><?= icon('hourglass', 22) ?></span>
            <span>
                <span class="brand-name">TIMEKEEPER</span>
                <span class="brand-tag"><?= e(mb_strtoupper($tagline)) ?></span>
            </span>
        </a>
        <nav class="side-nav">
            <a class="<?= $nav === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(url('/')) ?>"><?= icon('dashboard') ?> Dashboard</a>
            <a class="<?= $nav === 'archives' ? 'is-active' : '' ?>" href="<?= e(url('/archivos')) ?>"><?= icon('scroll') ?> Archivos</a>
            <a class="<?= $nav === 'timelines' ? 'is-active' : '' ?>" href="<?= e(url('/lineas')) ?>"><?= icon('hourglass') ?> Líneas</a>
            <a class="<?= $nav === 'metrics' ? 'is-active' : '' ?>" href="<?= e(url('/metricas')) ?>"><?= icon('chart') ?> Métricas</a>
            <a class="<?= $nav === 'search' ? 'is-active' : '' ?>" href="<?= e(url('/buscar')) ?>"><?= icon('search') ?> Buscar</a>
            <a class="<?= $nav === 'favorites' ? 'is-active' : '' ?>" href="<?= e(url('/favoritos')) ?>"><?= icon('favorites') ?> Favoritos</a>
            <div class="nav-rule"></div>
            <a class="<?= $nav === 'trash' ? 'is-active' : '' ?>" href="<?= e(url('/papelera')) ?>"><?= icon('trash') ?> Papelera</a>
            <a class="<?= $nav === 'settings' ? 'is-active' : '' ?>" href="<?= e(url('/ajustes')) ?>"><?= icon('settings') ?> Ajustes</a>
        </nav>
        <div class="sidebar-foot">
            <div class="user-chip">
                <span class="avatar"><?= e(initials((string) ($user['name'] ?? 'TK'))) ?></span>
                <span class="user-name"><?= e((string) ($user['name'] ?? '')) ?></span>
            </div>
            <form method="post" action="<?= e(form_action()) ?>">
                <?= csrf_field() ?>
                <?= route_field('/logout') ?>
                <button class="btn btn-ghost btn-small" type="submit"><?= icon('logout', 14) ?> Salir</button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>

    <div class="main-column">
        <header class="topbar">
            <button type="button" class="icon-btn sidebar-toggle" id="sidebarToggle" aria-label="Menú"><?= icon('filter', 18) ?></button>
            <form class="top-search" action="<?= e(url('/buscar')) ?>" method="get">
                <input type="search" name="q" placeholder="Buscar en el archivo…" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            </form>
            <button type="button" class="icon-btn" id="paletteBtn" title="Comandos (Ctrl+K)" aria-label="Comandos"><?= icon('diamond', 16) ?></button>
        </header>

        <?php if ($success): ?><div class="flash flash-ok" role="status"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="flash flash-err" role="alert"><?= e($error) ?></div><?php endif; ?>

        <main class="page">
            <?php require $templateFile; ?>
        </main>

        <footer class="statusbar">
            <span>TIMEKEEPER v<?= e((string) app_config('version', '1.0.0')) ?></span>
            <span><?= e(mb_strtoupper((string) ($title ?? 'ARCHIVO'))) ?></span>
            <span>N nuevo · F buscar · Ctrl+K comandos</span>
        </footer>
    </div>
</div>

<div class="palette" id="palette" hidden>
    <div class="palette-panel" role="dialog" aria-label="Comandos">
        <div class="palette-head">
            <span>Ctrl+K · Esc cierra</span>
            <button type="button" class="icon-btn" id="paletteClose" aria-label="Cerrar">×</button>
        </div>
        <input type="search" id="paletteInput" placeholder="> buscar comando" autocomplete="off">
        <ul id="paletteList"></ul>
    </div>
</div>
<script>
window.TK = {
    urls: {
        home: <?= json_encode(url('/')) ?>,
        archives: <?= json_encode(url('/archivos')) ?>,
        archiveNew: <?= json_encode(url('/archivos/nuevo')) ?>,
        timelines: <?= json_encode(url('/lineas')) ?>,
        timelineNew: <?= json_encode(url('/lineas/nueva')) ?>,
        search: <?= json_encode(url('/buscar')) ?>,
        metrics: <?= json_encode(url('/metricas')) ?>,
        settings: <?= json_encode(url('/ajustes')) ?>,
        favorites: <?= json_encode(url('/favoritos')) ?>,
        trash: <?= json_encode(url('/papelera')) ?>
    },
    archives: <?= json_encode($palette['archives'], JSON_UNESCAPED_UNICODE) ?>,
    timelines: <?= json_encode($palette['timelines'], JSON_UNESCAPED_UNICODE) ?>,
    archiveUrl: <?= json_encode(url('/archivos/')) ?>,
    timelineUrl: <?= json_encode(url('/lineas/')) ?>
};
</script>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/js/app.js') ?>"></script>
<script src="<?= e(url('/assets/js/timeline.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/js/timeline.js') ?>"></script>
</body>
</html>

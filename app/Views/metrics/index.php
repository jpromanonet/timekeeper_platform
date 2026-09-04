<?php
$cards = $metrics['cards'] ?? [];
$payload = [
    'byArchive' => $metrics['by_archive'] ?? [],
    'byPrecision' => $metrics['by_precision'] ?? [],
    'byTimeline' => $metrics['by_timeline'] ?? [],
    'byYear' => $metrics['by_year'] ?? [],
    'composition' => $metrics['composition'] ?? [],
    'byStatus' => $metrics['by_status'] ?? [],
];
$kpis = [
    ['Eventos', (int) ($cards['events'] ?? 0), 'sand'],
    ['Líneas', (int) ($cards['timelines'] ?? 0), 'blue'],
    ['Archivos', (int) ($cards['archives'] ?? 0), 'lavender'],
    ['Hitos', (int) ($cards['milestones'] ?? 0), 'clay'],
    ['Tags', (int) ($cards['tags'] ?? 0), 'sage'],
    ['Favoritos', (int) ($cards['favorites'] ?? 0), 'rose'],
    ['En curso', (int) ($cards['ongoing'] ?? 0), 'blue'],
    ['Sin fecha', (int) ($cards['undated'] ?? 0), 'muted'],
    ['Borradores', (int) ($cards['drafts'] ?? 0), 'sand'],
    ['Líneas archivadas', (int) ($cards['archived_timelines'] ?? 0), 'clay'],
];
?>
<div class="page-head">
    <div>
        <p class="kicker">ARCHIVO</p>
        <h1>Métricas</h1>
        <p class="lede">Composición del archivo: volumen, precisión temporal y peso de cada línea.</p>
    </div>
</div>

<section class="metric-cards">
    <?php foreach ($kpis as [$label, $value, $tone]): ?>
        <article class="metric-card tone-<?= e($tone) ?>">
            <span class="metric-label"><?= e($label) ?></span>
            <strong class="metric-value"><?= (int) $value ?></strong>
        </article>
    <?php endforeach; ?>
</section>

<div class="chart-grid">
    <section class="panel chart-box">
        <h2>Por archivo</h2>
        <p class="muted">Torta · eventos en cada archivo</p>
        <canvas id="chartPie" height="220"></canvas>
    </section>
    <section class="panel chart-box">
        <h2>Precisión de fechas</h2>
        <p class="muted">Torta · día, mes, año, década, siglo</p>
        <canvas id="chartPrecision" height="220"></canvas>
    </section>
    <section class="panel chart-box is-wide">
        <h2>Eventos por línea</h2>
        <p class="muted">Barras · las 12 líneas con más eventos</p>
        <canvas id="chartBars" height="160"></canvas>
    </section>
    <section class="panel chart-box is-wide">
        <h2>Eventos en el tiempo</h2>
        <p class="muted">Línea · cantidad por año de inicio</p>
        <canvas id="chartLine" height="160"></canvas>
    </section>
    <section class="panel chart-box is-wide">
        <h2>Composición por década</h2>
        <p class="muted">Apilado · regulares, hitos y en curso</p>
        <canvas id="chartStack" height="180"></canvas>
    </section>
    <section class="panel chart-box">
        <h2>Estado de las líneas</h2>
        <p class="muted">Torta · activa, borrador, archivada</p>
        <canvas id="chartStatus" height="220"></canvas>
    </section>
</div>

<script type="application/json" id="metrics-data"><?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(url('/assets/js/metrics.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/js/metrics.js') ?>"></script>

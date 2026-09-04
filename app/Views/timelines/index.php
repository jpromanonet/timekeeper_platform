<div class="page-head">
    <div>
        <p class="kicker">LÍNEAS</p>
        <h1>Todas las líneas</h1>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/lineas/nueva')) ?>"><?= icon('plus', 14) ?> Nueva línea</a>
</div>

<?php
$grouped = [];
foreach ($timelines as $tl) {
    $archive = (string) ($tl['collection_name'] ?: 'Sin archivo');
    if (!isset($grouped[$archive])) {
        $grouped[$archive] = ['series' => [], 'loose' => []];
    }
    if (!empty($tl['series_name'])) {
        $grouped[$archive]['series'][(string) $tl['series_name']][] = $tl;
    } else {
        $grouped[$archive]['loose'][] = $tl;
    }
}
?>

<?php if ($timelines): ?>
<form class="bulk-form" method="post" action="<?= e(form_action()) ?>" data-bulk data-bulk-confirm="¿Enviar las líneas tildadas a la papelera?">
    <?= csrf_field() ?>
    <?= route_field('/lineas/eliminar-lote') ?>
    <input type="hidden" name="back" value="/lineas">
    <?php $selectLabel = 'Seleccionar líneas'; $deleteLabel = 'Eliminar líneas'; require dirname(__DIR__) . '/partials/bulk_bar.php'; ?>
    <?php foreach ($grouped as $archiveName => $bucket): ?>
        <h2 class="section-title"><?= e($archiveName) ?></h2>
        <?php foreach ($bucket['series'] as $serieName => $items): ?>
            <h3 class="serie-title"><?= e($serieName) ?></h3>
            <div class="stack-cards">
                <?php foreach ($items as $tl): ?>
                    <div class="line-row">
                        <label class="tick" title="Seleccionar">
                            <input type="checkbox" name="ids[]" value="<?= (int) $tl['id'] ?>">
                        </label>
                        <a class="line-row-main" href="<?= e(url('/lineas/' . (int) $tl['id'])) ?>">
                            <span class="dot" style="background: <?= e((string) $tl['color']) ?>"></span>
                            <span>
                                <strong><?= e((string) $tl['name']) ?></strong>
                                <span class="muted"> · <?= e(status_label((string) $tl['status'])) ?></span>
                            </span>
                            <span class="muted"><?= (int) $tl['event_count'] ?> eventos</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($bucket['loose']): ?>
            <?php if ($bucket['series']): ?>
                <h3 class="serie-title">Sin serie</h3>
            <?php endif; ?>
            <div class="stack-cards">
                <?php foreach ($bucket['loose'] as $tl): ?>
                    <div class="line-row">
                        <label class="tick" title="Seleccionar">
                            <input type="checkbox" name="ids[]" value="<?= (int) $tl['id'] ?>">
                        </label>
                        <a class="line-row-main" href="<?= e(url('/lineas/' . (int) $tl['id'])) ?>">
                            <span class="dot" style="background: <?= e((string) $tl['color']) ?>"></span>
                            <span>
                                <strong><?= e((string) $tl['name']) ?></strong>
                                <span class="muted"> · <?= e(status_label((string) $tl['status'])) ?></span>
                            </span>
                            <span class="muted"><?= (int) $tl['event_count'] ?> eventos</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</form>
<?php else: ?>
    <p class="empty">No hay líneas todavía.</p>
<?php endif; ?>

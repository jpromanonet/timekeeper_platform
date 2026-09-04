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
    $key = $tl['collection_name'] ?: 'Sin archivo';
    $grouped[$key][] = $tl;
}
?>

<?php if ($timelines): ?>
<form class="bulk-form" method="post" action="<?= e(form_action()) ?>" data-bulk data-bulk-confirm="¿Enviar las líneas tildadas a la papelera?">
    <?= csrf_field() ?>
    <?= route_field('/lineas/eliminar-lote') ?>
    <input type="hidden" name="back" value="/lineas">
    <?php $selectLabel = 'Seleccionar líneas'; $deleteLabel = 'Eliminar líneas'; require dirname(__DIR__) . '/partials/bulk_bar.php'; ?>
    <?php foreach ($grouped as $group => $items): ?>
        <h2 class="section-title"><?= e($group) ?></h2>
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
</form>
<?php else: ?>
    <p class="empty">No hay líneas todavía.</p>
<?php endif; ?>

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
<?php foreach ($grouped as $group => $items): ?>
    <h2 class="section-title"><?= e($group) ?></h2>
    <div class="stack-cards">
        <?php foreach ($items as $tl): ?>
            <a class="line-row" href="<?= e(url('/lineas/' . (int) $tl['id'])) ?>">
                <span class="dot" style="background: <?= e((string) $tl['color']) ?>"></span>
                <span>
                    <strong><?= e((string) $tl['name']) ?></strong>
                    <span class="muted"> · <?= e(status_label((string) $tl['status'])) ?></span>
                </span>
                <span class="muted"><?= (int) $tl['event_count'] ?> eventos</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php if (!$timelines): ?>
    <p class="empty">No hay líneas todavía.</p>
<?php endif; ?>

<div class="page-head">
    <div>
        <p class="kicker">ARCHIVOS</p>
        <h1>Colecciones</h1>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/archivos/nuevo')) ?>"><?= icon('plus', 14) ?> Nuevo archivo</a>
</div>

<?php if ($archives): ?>
<form class="bulk-form" method="post" action="<?= e(form_action()) ?>" data-bulk data-bulk-confirm="¿Eliminar los archivos tildados? Las líneas quedan independientes.">
    <?= csrf_field() ?>
    <?= route_field('/archivos/eliminar-lote') ?>
    <input type="hidden" name="back" value="/archivos">
    <?php $selectLabel = 'Seleccionar archivos'; $deleteLabel = 'Eliminar archivos'; require dirname(__DIR__) . '/partials/bulk_bar.php'; ?>
    <div class="card-grid">
        <?php foreach ($archives as $archive): ?>
            <article class="file-card" style="--accent: <?= e((string) $archive['color']) ?>">
                <label class="tick tick-card" title="Seleccionar">
                    <input type="checkbox" name="ids[]" value="<?= (int) $archive['id'] ?>">
                </label>
                <a href="<?= e(url('/archivos/' . (int) $archive['id'])) ?>">
                    <div class="file-card-ico"><?= icon((string) $archive['icon'], 24) ?></div>
                    <h2><?= e((string) $archive['name']) ?></h2>
                    <p class="muted"><?= e((string) ($archive['description'] ?? '')) ?></p>
                    <p class="count-pill"><?= (int) $archive['timeline_count'] ?> líneas · <?= (int) $archive['event_count'] ?> eventos</p>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
</form>
<?php endif; ?>

<?php if ($ungrouped): ?>
    <h2 class="section-title">Sin archivo</h2>
    <form class="bulk-form" method="post" action="<?= e(form_action()) ?>" data-bulk data-bulk-confirm="¿Enviar las líneas tildadas a la papelera?">
        <?= csrf_field() ?>
        <?= route_field('/lineas/eliminar-lote') ?>
        <input type="hidden" name="back" value="/archivos">
        <?php $selectLabel = 'Seleccionar líneas'; $deleteLabel = 'Eliminar líneas'; require dirname(__DIR__) . '/partials/bulk_bar.php'; ?>
        <div class="stack-cards">
            <?php foreach ($ungrouped as $tl): ?>
                <div class="line-row">
                    <label class="tick" title="Seleccionar">
                        <input type="checkbox" name="ids[]" value="<?= (int) $tl['id'] ?>">
                    </label>
                    <a class="line-row-main" href="<?= e(url('/lineas/' . (int) $tl['id'])) ?>">
                        <span class="dot" style="background: <?= e((string) ($tl['color'] ?? '#C9B58A')) ?>"></span>
                        <span><strong><?= e((string) $tl['name']) ?></strong></span>
                        <span class="muted"><?= (int) $tl['event_count'] ?> eventos</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
<?php endif; ?>

<?php /** @var array $archive */ ?>
<div class="page-head">
    <div>
        <p class="kicker" style="color: <?= e((string) $archive['color']) ?>"><?= icon((string) $archive['icon'], 16) ?> ARCHIVO</p>
        <h1><?= e((string) $archive['name']) ?></h1>
        <?php if ($archive['description']): ?><p class="lede"><?= e((string) $archive['description']) ?></p><?php endif; ?>
        <p class="muted"><?= (int) $archive['timeline_count'] ?> líneas · <?= (int) $archive['event_count'] ?> eventos</p>
    </div>
    <div class="btn-row">
        <a class="btn" href="<?= e(url('/lineas/nueva?archivo=' . (int) $archive['id'])) ?>"><?= icon('plus', 14) ?> Nueva línea</a>
        <a class="btn" href="<?= e(url('/archivos/' . (int) $archive['id'] . '/editar')) ?>">Editar</a>
        <form method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Eliminar este archivo? Las líneas quedan independientes.');">
            <?= csrf_field() ?>
            <?= route_field('/archivos/' . (int) $archive['id'] . '/eliminar') ?>
            <button class="btn btn-danger" type="submit">Eliminar archivo</button>
        </form>
    </div>
</div>

<?php if ($timelines): ?>
<form class="bulk-form" method="post" action="<?= e(form_action()) ?>" data-bulk data-bulk-confirm="¿Enviar las líneas tildadas a la papelera?">
    <?= csrf_field() ?>
    <?= route_field('/lineas/eliminar-lote') ?>
    <input type="hidden" name="back" value="/archivos/<?= (int) $archive['id'] ?>">
    <?php $selectLabel = 'Seleccionar líneas'; $deleteLabel = 'Eliminar líneas'; require dirname(__DIR__) . '/partials/bulk_bar.php'; ?>
    <div class="stack-cards">
        <?php foreach ($timelines as $tl): ?>
            <div class="line-row">
                <label class="tick" title="Seleccionar">
                    <input type="checkbox" name="ids[]" value="<?= (int) $tl['id'] ?>">
                </label>
                <a class="line-row-main" href="<?= e(url('/lineas/' . (int) $tl['id'])) ?>">
                    <span class="dot" style="background: <?= e((string) $tl['color']) ?>"></span>
                    <span>
                        <strong><?= e((string) $tl['name']) ?></strong>
                        <?php if ($tl['description']): ?><span class="muted block"><?= e((string) $tl['description']) ?></span><?php endif; ?>
                    </span>
                    <span class="muted">
                        <?= (int) $tl['event_count'] ?> eventos
                        <?php if (!empty($tl['first_event'])): ?>
                            · <?= e(substr((string) $tl['first_event'], 0, 4)) ?> → <?= !empty($tl['last_event']) ? e(substr((string) $tl['last_event'], 0, 4)) : 'hoy' ?>
                        <?php endif; ?>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</form>
<?php else: ?>
    <p class="empty">Este archivo todavía no tiene líneas.</p>
<?php endif; ?>

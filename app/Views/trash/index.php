<div class="page-head">
    <h1>Papelera</h1>
</div>
<p class="muted">Nada se borra del todo hasta que lo purgás.</p>

<h2 class="section-title">Líneas</h2>
<?php if (!$timelines): ?>
    <p class="muted">Vacía.</p>
<?php endif; ?>
<?php foreach ($timelines as $tl): ?>
    <div class="line-row is-actions">
        <span><?= e((string) $tl['name']) ?> <span class="muted"><?= e((string) ($tl['collection_name'] ?? '')) ?></span></span>
        <div class="btn-row">
            <form method="post" action="<?= e(form_action()) ?>"><?= csrf_field() ?><?= route_field('/papelera/lineas/' . (int) $tl['id'] . '/restaurar') ?><button class="btn btn-small" type="submit">Restaurar</button></form>
            <form method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Eliminar definitivamente?');"><?= csrf_field() ?><?= route_field('/papelera/lineas/' . (int) $tl['id'] . '/purgar') ?><button class="btn btn-small btn-danger" type="submit">Eliminar</button></form>
        </div>
    </div>
<?php endforeach; ?>

<h2 class="section-title">Eventos</h2>
<?php if (!$events): ?>
    <p class="muted">Vacía.</p>
<?php endif; ?>
<?php foreach ($events as $ev): ?>
    <div class="line-row is-actions">
        <span><?= e((string) $ev['title']) ?> <span class="muted"><?= e((string) $ev['timeline_name']) ?></span></span>
        <div class="btn-row">
            <form method="post" action="<?= e(form_action()) ?>"><?= csrf_field() ?><?= route_field('/papelera/eventos/' . (int) $ev['id'] . '/restaurar') ?><button class="btn btn-small" type="submit">Restaurar</button></form>
            <form method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Eliminar definitivamente?');"><?= csrf_field() ?><?= route_field('/papelera/eventos/' . (int) $ev['id'] . '/purgar') ?><button class="btn btn-small btn-danger" type="submit">Eliminar</button></form>
        </div>
    </div>
<?php endforeach; ?>

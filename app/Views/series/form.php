<?php
$isEdit = !empty($serie);
$color = (string) ($serie['color'] ?? $defaultColor ?? '#C9B58A');
$archiveId = (int) $archive['id'];
?>
<div class="page-head">
    <div>
        <p class="kicker"><?= e((string) $archive['name']) ?></p>
        <h1><?= $isEdit ? 'Editar serie' : 'Nueva serie' ?></h1>
    </div>
</div>
<form class="panel form-grid" method="post" action="<?= e(form_action()) ?>">
    <?= csrf_field() ?>
    <?= route_field($isEdit ? '/archivos/' . $archiveId . '/series/' . (int) $serie['id'] : '/archivos/' . $archiveId . '/series') ?>
    <label>Nombre
        <input type="text" name="name" required maxlength="160" value="<?= e((string) ($serie['name'] ?? '')) ?>">
    </label>
    <label>Color
        <input type="color" name="color" value="<?= e($color) ?>">
    </label>
    <div class="btn-row">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= e(url('/archivos/' . $archiveId)) ?>">Cancelar</a>
    </div>
</form>
<?php if ($isEdit): ?>
    <form class="danger-box" method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Eliminar esta serie? Las líneas quedan en el archivo.');">
        <?= csrf_field() ?>
        <?= route_field('/archivos/' . $archiveId . '/series/' . (int) $serie['id'] . '/eliminar') ?>
        <button class="btn btn-danger" type="submit">Eliminar serie</button>
    </form>
<?php endif; ?>

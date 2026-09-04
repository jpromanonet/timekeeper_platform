<?php $isEdit = !empty($archive); ?>
<div class="page-head">
    <h1><?= $isEdit ? 'Editar archivo' : 'Nuevo archivo' ?></h1>
</div>
<form class="panel form-grid" method="post" action="<?= e(form_action()) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?= route_field($isEdit ? '/archivos/' . (int) $archive['id'] : '/archivos') ?>
    <label>Nombre
        <input type="text" name="name" required maxlength="160" value="<?= e((string) ($archive['name'] ?? '')) ?>">
    </label>
    <label>Descripción
        <textarea name="description" rows="3"><?= e((string) ($archive['description'] ?? '')) ?></textarea>
    </label>
    <fieldset>
        <legend>Icono</legend>
        <div class="icon-picker">
            <?php foreach (archive_icons() as $key => $label): ?>
                <label class="icon-opt">
                    <input type="radio" name="icon" value="<?= e($key) ?>" <?= (($archive['icon'] ?? 'scroll') === $key) ? 'checked' : '' ?>>
                    <?= icon($key, 18) ?>
                    <span><?= e($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <label>Color
        <input type="color" name="color" value="<?= e((string) ($archive['color'] ?? '#91A7C4')) ?>">
    </label>
    <label>Imagen opcional
        <input type="file" name="cover" accept="image/*">
    </label>
    <div class="btn-row">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= e(url($isEdit ? '/archivos/' . (int) $archive['id'] : '/archivos')) ?>">Cancelar</a>
    </div>
</form>
<?php if ($isEdit): ?>
    <form class="danger-box" method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Eliminar este archivo? Las líneas quedan independientes.');">
        <?= csrf_field() ?>
        <?= route_field('/archivos/' . (int) $archive['id'] . '/eliminar') ?>
        <button class="btn btn-danger" type="submit">Eliminar archivo</button>
    </form>
<?php endif; ?>

<?php
$isEdit = !empty($timeline);
$collectionId = $timeline['collection_id'] ?? $preselect;
$seriesList = $seriesList ?? [];
$seriesId = $timeline['series_id'] ?? ($preselectSeries ?? null);
?>
<div class="page-head">
    <h1><?= $isEdit ? 'Editar línea' : 'Nueva línea' ?></h1>
</div>
<form class="panel form-grid" method="post" action="<?= e(form_action()) ?>" enctype="multipart/form-data" data-series-form>
    <?= csrf_field() ?>
    <?= route_field($isEdit ? '/lineas/' . (int) $timeline['id'] : '/lineas') ?>
    <label>Nombre
        <input type="text" name="name" required maxlength="190" value="<?= e((string) ($timeline['name'] ?? '')) ?>">
    </label>
    <label>Descripción
        <textarea name="description" rows="3"><?= e((string) ($timeline['description'] ?? '')) ?></textarea>
    </label>
    <label>Archivo
        <select name="collection_id">
            <option value="">— Independiente —</option>
            <?php foreach ($archives as $archive): ?>
                <option value="<?= (int) $archive['id'] ?>" <?= ((int) $collectionId === (int) $archive['id']) ? 'selected' : '' ?>>
                    <?= e((string) $archive['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label data-series-wrap>
        Serie <span class="muted">(opcional)</span>
        <select name="series_id">
            <option value="">— Sin serie —</option>
            <?php foreach ($seriesList as $serie): ?>
                <option
                    value="<?= (int) $serie['id'] ?>"
                    data-archive="<?= (int) $serie['collection_id'] ?>"
                    <?= ((int) $seriesId === (int) $serie['id']) ? 'selected' : '' ?>
                ><?= e((string) $serie['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <span class="muted">¿A qué serie la agregás? Podés dejarla suelta en el archivo.</span>
    </label>
    <fieldset>
        <legend>Icono</legend>
        <div class="icon-picker">
            <?php foreach (archive_icons() as $key => $label): ?>
                <label class="icon-opt">
                    <input type="radio" name="icon" value="<?= e($key) ?>" <?= (($timeline['icon'] ?? 'hourglass') === $key) ? 'checked' : '' ?>>
                    <?= icon($key, 18) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <label>Color
        <input type="color" name="color" value="<?= e((string) ($timeline['color'] ?? '#C9B58A')) ?>">
    </label>
    <label>Vista predeterminada
        <select name="default_view">
            <option value="vertical" <?= (($timeline['default_view'] ?? $prefs['default_view']) === 'vertical') ? 'selected' : '' ?>>Vertical</option>
            <option value="horizontal" <?= (($timeline['default_view'] ?? $prefs['default_view']) === 'horizontal') ? 'selected' : '' ?>>Horizontal</option>
        </select>
    </label>
    <label>Formato de fecha
        <select name="date_format">
            <?php foreach (['d/m/Y' => 'DD/MM/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'm/d/Y' => 'MM/DD/YYYY'] as $val => $lab): ?>
                <option value="<?= e($val) ?>" <?= (($timeline['date_format'] ?? $prefs['date_format']) === $val) ? 'selected' : '' ?>><?= e($lab) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Estado
        <select name="status">
            <?php foreach (['active' => 'Activa', 'draft' => 'Borrador', 'archived' => 'Archivada'] as $val => $lab): ?>
                <option value="<?= e($val) ?>" <?= (($timeline['status'] ?? 'active') === $val) ? 'selected' : '' ?>><?= e($lab) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Fecha inicial (opcional)
        <input type="text" name="start_date" placeholder="1991 / 09/2026 / siglo XIX" value="<?= e((string) ($timeline['start_date'] ?? '')) ?>">
    </label>
    <label>Fecha final (opcional)
        <input type="text" name="end_date" value="<?= e((string) ($timeline['end_date'] ?? '')) ?>">
    </label>
    <?php if (!$isEdit): ?>
        <label>Categorías iniciales (separadas por coma)
            <input type="text" name="categories" placeholder="Hitos, Equipo, Finanzas">
        </label>
    <?php endif; ?>
    <label>Portada
        <?php if (!empty($timeline['cover_image'])): ?>
            <img class="media-preview" src="<?= e(media_url((string) $timeline['cover_image'])) ?>" alt="Portada actual">
        <?php endif; ?>
        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <div class="btn-row">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= e(url($isEdit ? '/lineas/' . (int) $timeline['id'] : '/lineas')) ?>">Cancelar</a>
    </div>
</form>

<?php if ($isEdit): ?>
    <section class="panel">
        <h2>Categorías</h2>
        <?php foreach ($categories as $cat): ?>
            <form class="inline-form" method="post" action="<?= e(form_action()) ?>">
                <?= csrf_field() ?>
                <?= route_field('/lineas/' . (int) $timeline['id'] . '/categorias/' . (int) $cat['id']) ?>
                <input type="text" name="name" value="<?= e((string) $cat['name']) ?>" required>
                <input type="color" name="color" value="<?= e((string) $cat['color']) ?>">
                <button class="btn btn-small" type="submit">OK</button>
            </form>
            <form class="inline-form" method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Quitar categoría?');">
                <?= csrf_field() ?>
                <?= route_field('/lineas/' . (int) $timeline['id'] . '/categorias/' . (int) $cat['id'] . '/eliminar') ?>
                <button class="btn btn-small btn-danger" type="submit">X</button>
            </form>
        <?php endforeach; ?>
        <form class="inline-form" method="post" action="<?= e(form_action()) ?>">
            <?= csrf_field() ?>
            <?= route_field('/lineas/' . (int) $timeline['id'] . '/categorias') ?>
            <input type="text" name="name" placeholder="Nueva categoría" required>
            <input type="color" name="color" value="#91A7C4">
            <button class="btn btn-small" type="submit">Agregar</button>
        </form>
    </section>
    <div class="btn-row">
        <form method="post" action="<?= e(form_action()) ?>">
            <?= csrf_field() ?>
            <?= route_field('/lineas/' . (int) $timeline['id'] . '/duplicar') ?>
            <button class="btn" type="submit">Duplicar línea</button>
        </form>
        <form method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Enviar a la papelera?');">
            <?= csrf_field() ?>
            <?= route_field('/lineas/' . (int) $timeline['id'] . '/eliminar') ?>
            <button class="btn btn-danger" type="submit">Eliminar</button>
        </form>
    </div>
<?php endif; ?>

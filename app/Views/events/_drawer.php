<?php
$ev = $editEvent ?? null;
$isEdit = $ev !== null;
$action = $isEdit
    ? '/lineas/' . (int) $timeline['id'] . '/eventos/' . (int) $ev['id']
    : '/lineas/' . (int) $timeline['id'] . '/eventos';
?>
<aside class="drawer <?= !empty($openDrawer) ? 'is-open' : '' ?>" id="eventDrawer" aria-hidden="<?= empty($openDrawer) ? 'true' : 'false' ?>">
    <form method="post" action="<?= e(form_action()) ?>" enctype="multipart/form-data" class="drawer-form">
        <?= csrf_field() ?>
        <?= route_field($action) ?>
        <header class="drawer-head">
            <h2><?= $isEdit ? 'Editar evento' : 'Nuevo evento' ?></h2>
            <a class="icon-btn" href="<?= e(url('/lineas/' . (int) $timeline['id'])) ?>" id="closeDrawer" aria-label="Cerrar">×</a>
        </header>

        <label>Título
            <input type="text" name="title" required maxlength="255" value="<?= e((string) ($ev['title'] ?? '')) ?>">
        </label>
        <label>Fecha
            <input type="text" name="start_input" placeholder="04/09/2026 · 09/2026 · 1991 · década de 1990 · siglo XIX" value="<?= e($ev ? DatePrecision::inputValue($ev['start_date'], (string) $ev['date_precision']) : '') ?>">
        </label>
        <label>Precisión
            <select name="date_precision">
                <?php foreach (DatePrecision::labels() as $val => $lab): ?>
                    <option value="<?= e($val) ?>" <?= (($ev['date_precision'] ?? 'day') === $val) ? 'selected' : '' ?>><?= e($lab) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-2">
            <label>Hora
                <input type="time" name="start_time" value="<?= e(DatePrecision::formatTime($ev['start_time'] ?? null)) ?>">
            </label>
            <label>Hasta
                <input type="text" name="end_input" value="<?= e($ev && $ev['end_date'] ? DatePrecision::inputValue($ev['end_date'], (string) ($ev['end_date_precision'] ?? $ev['date_precision'])) : '') ?>">
            </label>
        </div>
        <label>Categoría
            <select name="category_id">
                <option value="">—</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= ((int) ($ev['category_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>><?= e((string) $cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Resumen
            <textarea name="summary" rows="2"><?= e((string) ($ev['summary'] ?? '')) ?></textarea>
        </label>
        <label>Descripción
            <textarea name="description" rows="5"><?= e((string) ($ev['description'] ?? '')) ?></textarea>
        </label>
        <label>Tags
            <input type="text" name="tags" placeholder="SoupIT, Launch" value="<?= e(implode(', ', $ev['tag_list'] ?? [])) ?>">
        </label>
        <label>Ubicación
            <input type="text" name="location" value="<?= e((string) ($ev['location'] ?? '')) ?>">
        </label>
        <label>Personas
            <input type="text" name="people" value="<?= e((string) ($ev['people'] ?? '')) ?>">
        </label>
        <label>URL
            <input type="url" name="url" value="<?= e((string) ($ev['url'] ?? '')) ?>">
        </label>
        <label>Video (URL)
            <input type="url" name="video_url" value="<?= e((string) ($ev['video_url'] ?? '')) ?>">
        </label>
        <label>Imagen
            <?php if (!empty($ev['image'])): ?>
                <img class="media-preview" src="<?= e(media_url((string) $ev['image'])) ?>" alt="Imagen actual">
            <?php endif; ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
        </label>
        <label>Notas privadas
            <textarea name="notes" rows="3"><?= e((string) ($ev['notes'] ?? '')) ?></textarea>
        </label>
        <label class="check"><input type="checkbox" name="is_milestone" value="1" <?= !empty($ev['is_milestone']) ? 'checked' : '' ?>> Hito</label>
        <label class="check"><input type="checkbox" name="is_ongoing" value="1" <?= !empty($ev['is_ongoing']) ? 'checked' : '' ?>> En curso</label>

        <button class="btn btn-primary" type="submit">Guardar evento</button>
    </form>

    <?php if ($isEdit): ?>
        <div class="drawer-actions">
            <form method="post" action="<?= e(form_action()) ?>">
                <?= csrf_field() ?>
                <?= route_field('/lineas/' . (int) $timeline['id'] . '/eventos/' . (int) $ev['id'] . '/favorito') ?>
                <button class="btn" type="submit"><?= !empty($ev['is_favorite']) ? 'Quitar estrella' : '★ Favorito' ?></button>
            </form>
            <form method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Enviar a la papelera?');">
                <?= csrf_field() ?>
                <?= route_field('/lineas/' . (int) $timeline['id'] . '/eventos/' . (int) $ev['id'] . '/eliminar') ?>
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        </div>
    <?php endif; ?>
</aside>
<div class="drawer-mask <?= !empty($openDrawer) ? 'is-open' : '' ?>" id="drawerMask"></div>

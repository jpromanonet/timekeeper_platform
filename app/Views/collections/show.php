<?php
/** @var array $archive */
/** @var list<array> $series */
/** @var list<array> $timelines */
/** @var array{groups: list<array{serie: array, items: list<array>}>, loose: list<array>} $grouped */
/** @var string|int $serieFilter */
/** @var int|null $lineFilter */
$series = $series ?? [];
$timelines = $timelines ?? [];
$grouped = $grouped ?? ['groups' => [], 'loose' => $timelines];
$serieFilter = $serieFilter ?? 'all';
$lineFilter = $lineFilter ?? null;
$hasSeries = $series !== [];
$archiveId = (int) $archive['id'];
$rowPartial = dirname(__DIR__) . '/partials/line_row.php';

$query = [];
if ($serieFilter === 'none') {
    $query['serie'] = 'none';
} elseif (is_int($serieFilter)) {
    $query['serie'] = $serieFilter;
}
if ($lineFilter) {
    $query['linea'] = $lineFilter;
}
$backPath = '/archivos/' . $archiveId . ($query ? '?' . http_build_query($query) : '');
$newLineUrl = url('/lineas/nueva?archivo=' . $archiveId . (is_int($serieFilter) ? '&serie=' . $serieFilter : ''));

$visibleCount = count($grouped['loose']);
foreach ($grouped['groups'] as $group) {
    $visibleCount += count($group['items']);
}
?>
<div class="page-head">
    <div>
        <p class="kicker" style="color: <?= e((string) $archive['color']) ?>"><?= icon((string) $archive['icon'], 16) ?> ARCHIVO</p>
        <h1><?= e((string) $archive['name']) ?></h1>
        <?php if ($archive['description']): ?><p class="lede"><?= e((string) $archive['description']) ?></p><?php endif; ?>
        <p class="muted"><?= (int) $archive['timeline_count'] ?> líneas · <?= (int) $archive['event_count'] ?> eventos</p>
    </div>
    <div class="btn-row">
        <a class="btn btn-success" href="<?= e($newLineUrl) ?>"><?= icon('plus', 14) ?> Nueva línea</a>
        <a class="btn btn-soft" href="<?= e(url('/archivos/' . $archiveId . '/series/nueva')) ?>"><?= icon('plus', 14) ?> Nueva serie</a>
        <a class="btn" href="<?= e(url('/archivos/' . $archiveId . '/editar')) ?>">Editar</a>
        <form method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Eliminar este archivo? Las líneas quedan independientes.');">
            <?= csrf_field() ?>
            <?= route_field('/archivos/' . $archiveId . '/eliminar') ?>
            <button class="btn btn-danger" type="submit">Eliminar archivo</button>
        </form>
    </div>
</div>

<?php if ($timelines || $hasSeries): ?>
<div class="archive-tools">
    <form class="archive-filters" method="get" action="<?= e(form_action()) ?>">
        <input type="hidden" name="r" value="/archivos/<?= $archiveId ?>">
        <?php if ($hasSeries): ?>
        <label>Serie
            <select name="serie" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($series as $serie): ?>
                    <option value="<?= (int) $serie['id'] ?>" <?= (is_int($serieFilter) && $serieFilter === (int) $serie['id']) ? 'selected' : '' ?>>
                        <?= e((string) $serie['name']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="none" <?= $serieFilter === 'none' ? 'selected' : '' ?>>Sin serie</option>
            </select>
        </label>
        <?php endif; ?>
        <?php if ($timelines): ?>
        <label>Línea
            <select name="linea" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($timelines as $tlOpt): ?>
                    <option value="<?= (int) $tlOpt['id'] ?>" <?= ((int) $lineFilter === (int) $tlOpt['id']) ? 'selected' : '' ?>>
                        <?= e((string) $tlOpt['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
    </form>
    <div class="btn-row">
        <?php if ($hasSeries): ?>
            <button class="btn btn-danger" type="submit" form="bulk-series" data-bulk-submit disabled>Eliminar series</button>
        <?php endif; ?>
        <?php if ($visibleCount > 0): ?>
            <button class="btn btn-danger" type="submit" form="bulk-lines" data-bulk-submit disabled>Eliminar líneas</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasSeries): ?>
    <form id="bulk-series" class="ghost-form" method="post" action="<?= e(form_action()) ?>" data-bulk data-bulk-confirm="¿Eliminar las series tildadas? Las líneas quedan en el archivo.">
        <?= csrf_field() ?>
        <?= route_field('/archivos/' . $archiveId . '/series/eliminar-lote') ?>
        <input type="hidden" name="back" value="<?= e($backPath) ?>">
    </form>
<?php endif; ?>

<form id="bulk-lines" class="bulk-form" method="post" action="<?= e(form_action()) ?>" data-bulk data-bulk-confirm="¿Enviar las líneas tildadas a la papelera?">
    <?= csrf_field() ?>
    <?= route_field('/lineas/eliminar-lote') ?>
    <input type="hidden" name="back" value="<?= e($backPath) ?>">

    <?php if ($hasSeries && $serieFilter === 'all' && !$lineFilter): ?>
        <?php foreach ($grouped['groups'] as $group): ?>
            <?php $serie = $group['serie']; ?>
            <div class="serie-head">
                <label class="tick" title="Seleccionar serie">
                    <input type="checkbox" name="ids[]" value="<?= (int) $serie['id'] ?>" form="bulk-series">
                </label>
                <span class="dot" style="background: <?= e((string) $serie['color']) ?>"></span>
                <h2><?= e((string) $serie['name']) ?></h2>
                <span class="muted"><?= count($group['items']) ?> líneas</span>
                <a class="btn btn-small" href="<?= e(url('/archivos/' . $archiveId . '/series/' . (int) $serie['id'] . '/editar')) ?>">Editar</a>
                <a class="btn btn-small btn-success" href="<?= e(url('/lineas/nueva?archivo=' . $archiveId . '&serie=' . (int) $serie['id'])) ?>"><?= icon('plus', 14) ?> Nueva línea</a>
            </div>
            <?php if ($group['items']): ?>
                <div class="stack-cards">
                    <?php foreach ($group['items'] as $tl): ?>
                        <?php require $rowPartial; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty">Esta serie todavía no tiene líneas.</p>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($grouped['loose']): ?>
            <div class="serie-head is-loose">
                <h2>Sin serie</h2>
                <span class="muted"><?= count($grouped['loose']) ?> líneas</span>
            </div>
            <div class="stack-cards">
                <?php foreach ($grouped['loose'] as $tl): ?>
                    <?php require $rowPartial; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php elseif ($grouped['groups'] || $grouped['loose']): ?>
        <?php foreach ($grouped['groups'] as $group): ?>
            <?php $serie = $group['serie']; ?>
            <div class="serie-head">
                <label class="tick" title="Seleccionar serie">
                    <input type="checkbox" name="ids[]" value="<?= (int) $serie['id'] ?>" form="bulk-series">
                </label>
                <span class="dot" style="background: <?= e((string) $serie['color']) ?>"></span>
                <h2><?= e((string) $serie['name']) ?></h2>
                <span class="muted"><?= count($group['items']) ?> líneas</span>
                <a class="btn btn-small" href="<?= e(url('/archivos/' . $archiveId . '/series/' . (int) $serie['id'] . '/editar')) ?>">Editar</a>
            </div>
            <div class="stack-cards">
                <?php foreach ($group['items'] as $tl): ?>
                    <?php require $rowPartial; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($grouped['loose']): ?>
            <div class="stack-cards">
                <?php foreach ($grouped['loose'] as $tl): ?>
                    <?php require $rowPartial; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="empty"><?= $hasSeries ? 'No hay líneas en este filtro.' : 'Este archivo todavía no tiene líneas.' ?></p>
    <?php endif; ?>
</form>
    <?php if ($hasSeries && $timelines): ?>
        <?php foreach ($timelines as $tl): ?>
            <form id="move-serie-<?= (int) $tl['id'] ?>" class="ghost-form" method="post" action="<?= e(form_action()) ?>">
                <?= csrf_field() ?>
                <?= route_field('/lineas/' . (int) $tl['id'] . '/serie') ?>
                <input type="hidden" name="back" value="<?= e($backPath) ?>">
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
<?php else: ?>
    <p class="empty">Este archivo todavía no tiene líneas. Creá una serie o una línea para empezar.</p>
<?php endif; ?>

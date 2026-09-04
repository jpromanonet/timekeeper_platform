<?php
$kicker = trim(($timeline['collection_name'] ?? '') . (($timeline['series_name'] ?? '') ? ' / ' . $timeline['series_name'] : ''));
if ($kicker === '') {
    $kicker = 'LÍNEA INDEPENDIENTE';
}
$qs = $_GET;
unset($qs['r'], $qs['nuevo'], $qs['evento']);
$baseQuery = $qs;
$vertQs = $baseQuery; $vertQs['vista'] = 'vertical';
$horQs = $baseQuery; $horQs['vista'] = 'horizontal';
$newQs = $baseQuery; $newQs['nuevo'] = '1';
$side = 0;
?>
<div class="tl-head">
    <div>
        <p class="kicker"><?= e($kicker) ?></p>
        <h1><?= e((string) $timeline['name']) ?></h1>
        <?php if ($timeline['description']): ?><p class="lede"><?= e((string) $timeline['description']) ?></p><?php endif; ?>
        <?php if (!empty($timeline['cover_image'])): ?>
            <img class="cover-banner" src="<?= e(media_url((string) $timeline['cover_image'])) ?>" alt="">
        <?php endif; ?>
    </div>
    <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '?' . http_build_query($newQs))) ?>" id="newEventBtn"><?= icon('plus', 14) ?> Nuevo evento</a>
        <a class="btn" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '/editar')) ?>">Editar</a>
        <form method="post" action="<?= e(form_action()) ?>" onsubmit="return confirm('¿Enviar esta línea a la papelera?');">
            <?= csrf_field() ?>
            <?= route_field('/lineas/' . (int) $timeline['id'] . '/eliminar') ?>
            <button class="btn btn-danger" type="submit">Eliminar</button>
        </form>
        <details class="drop">
            <summary class="btn">Exportar</summary>
            <div class="drop-menu" role="menu">
                <a role="menuitem" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '/exportar.json')) ?>">JSON <span>datos</span></a>
                <a role="menuitem" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '/exportar.csv')) ?>">CSV <span>hoja</span></a>
                <a role="menuitem" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '/exportar.pdf')) ?>">PDF <span>línea de tiempo</span></a>
            </div>
        </details>
    </div>
</div>

<div class="tl-toolbar">
    <div class="view-toggle">
        <a class="<?= $viewMode === 'vertical' ? 'is-on' : '' ?>" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '?' . http_build_query($vertQs))) ?>">Vertical</a>
        <a class="<?= $viewMode === 'horizontal' ? 'is-on' : '' ?>" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '?' . http_build_query($horQs))) ?>">Horizontal</a>
    </div>
    <form class="filter-bar" method="get" action="<?= e(url('/lineas/' . (int) $timeline['id'])) ?>">
        <input type="hidden" name="vista" value="<?= e($viewMode) ?>">
        <input type="search" name="q" value="<?= e((string) $filters['q']) ?>" placeholder="Buscar evento">
        <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= ((int) $filters['category'] === (int) $cat['id']) ? 'selected' : '' ?>><?= e((string) $cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="tag" value="<?= e((string) ($filters['tag'] ?? '')) ?>" placeholder="Tag" list="tag-list">
        <datalist id="tag-list">
            <?php foreach ($allTags as $tagName): ?><option value="<?= e($tagName) ?>"><?php endforeach; ?>
        </datalist>
        <input type="text" name="desde" value="<?= e((string) ($_GET['desde'] ?? '')) ?>" placeholder="Desde">
        <input type="text" name="hasta" value="<?= e((string) ($_GET['hasta'] ?? '')) ?>" placeholder="Hasta">
        <button class="btn btn-small" type="submit">Filtrar</button>
    </form>
</div>

<?php if ($categories): ?>
    <div class="cat-pills">
        <a class="<?= !$filters['category'] ? 'is-on' : '' ?>" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '?vista=' . $viewMode)) ?>">Todas</a>
        <?php foreach ($categories as $cat): ?>
            <a class="<?= ((int) $filters['category'] === (int) $cat['id']) ? 'is-on' : '' ?>" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '?vista=' . $viewMode . '&categoria=' . (int) $cat['id'])) ?>" style="--cat: <?= e((string) $cat['color']) ?>"><?= e((string) $cat['name']) ?></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($viewMode === 'horizontal'): ?>
    <div class="tl-h-wrap">
        <div class="tl-h-controls">
            <button type="button" class="btn btn-small" data-h-zoom="-1">−</button>
            <span id="hZoomLabel">AÑOS</span>
            <button type="button" class="btn btn-small" data-h-zoom="1">+</button>
            <button type="button" class="btn btn-small" data-h-center>Centrar</button>
            <label class="go-date">Ir a fecha <input type="text" id="hGoto" placeholder="1991"></label>
        </div>
        <div class="tl-h" id="horizontalBoard" data-events="<?= e(json_encode($jsEvents, JSON_UNESCAPED_UNICODE)) ?>"></div>
        <div class="tl-minimap" id="minimap"></div>
    </div>
<?php else: ?>
    <div class="tl-vertical" id="verticalBoard">
        <?php foreach ($groups as $group): ?>
            <div class="tl-year"><?= e((string) $group['label']) ?></div>
            <?php foreach ($group['items'] as $ev): ?>
                <?php
                $side = 1 - $side;
                $color = $ev['color'] ?: ($ev['category_color'] ?? $timeline['color']);
                $label = DatePrecision::formatSpan(
                    $ev['start_date'],
                    (string) $ev['date_precision'],
                    $ev['end_date'],
                    $ev['end_date_precision'] ?? $ev['date_precision'],
                    (bool) $ev['is_ongoing'],
                    $dateFormat
                );
                if ($ev['start_time']) {
                    $label .= ' · ' . DatePrecision::formatTime($ev['start_time']);
                }
                ?>
                <?php if ((int) $ev['is_milestone'] === 1): ?>
                    <div class="tl-milestone">✦ <?= e($label) ?> ✦</div>
                <?php endif; ?>
                <article class="tl-item <?= $side ? 'is-left' : 'is-right' ?>">
                    <a class="tl-card" href="<?= e(url('/lineas/' . (int) $timeline['id'] . '?evento=' . (int) $ev['id'])) ?>" style="--cat: <?= e((string) $color) ?>">
                        <header><?= e($label) ?></header>
                        <h3><?= e((string) $ev['title']) ?></h3>
                        <?php if (!empty($ev['image'])): ?>
                            <img class="tl-photo" src="<?= e(media_url((string) $ev['image'])) ?>" alt="">
                        <?php endif; ?>
                        <?php if ($ev['summary']): ?><p><?= e((string) $ev['summary']) ?></p><?php endif; ?>
                        <footer>
                            <?php if ($ev['category_name']): ?><span class="chip"><?= e((string) $ev['category_name']) ?></span><?php endif; ?>
                            <?php foreach ($ev['tag_list'] ?? [] as $tag): ?><span class="tag">#<?= e($tag) ?></span><?php endforeach; ?>
                        </footer>
                    </a>
                    <span class="tl-node <?= (int) $ev['is_milestone'] ? 'is-star' : '' ?>" style="border-color: <?= e((string) $color) ?>"></span>
                    <div class="tl-spacer"></div>
                </article>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!$events): ?>
            <p class="empty">No hay eventos con esos filtros.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/events/_drawer.php'; ?>

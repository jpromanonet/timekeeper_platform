<div class="page-head">
    <h1>Buscador</h1>
</div>
<form class="panel filter-bar" method="get" action="<?= e(url('/buscar')) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Puestito, Astrid, 1991…" autofocus>
    <button class="btn btn-primary" type="submit">Buscar</button>
</form>

<?php if ($q !== '' && !$results): ?>
    <p class="empty">Nada bajo “<?= e($q) ?>”.</p>
<?php endif; ?>

<ul class="result-list">
    <?php foreach ($results as $row): ?>
        <li>
            <a href="<?= e(url('/lineas/' . (int) $row['timeline_id'] . '?evento=' . (int) $row['id'])) ?>">
                <span class="muted"><?= e(trim(($row['collection_name'] ? $row['collection_name'] . ' / ' : '') . $row['timeline_name'])) ?></span>
                <strong><?= e((string) $row['title']) ?></strong>
                <span><?= e(DatePrecision::format($row['start_date'], (string) $row['date_precision'], $dateFormat)) ?></span>
                <?php if ($row['summary']): ?><p class="muted"><?= e((string) $row['summary']) ?></p><?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

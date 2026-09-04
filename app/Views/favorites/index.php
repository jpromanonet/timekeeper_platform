<div class="page-head">
    <h1>Favoritos</h1>
</div>
<?php if (!$events): ?>
    <p class="empty">Todavía no marcaste hitos con estrella.</p>
<?php else: ?>
    <ul class="result-list">
        <?php foreach ($events as $ev): ?>
            <li>
                <a href="<?= e(url('/lineas/' . (int) $ev['timeline_id'] . '?evento=' . (int) $ev['id'])) ?>">
                    <span class="muted"><?= e(trim(($ev['collection_name'] ? $ev['collection_name'] . ' / ' : '') . $ev['timeline_name'])) ?></span>
                    <strong>★ <?= e((string) $ev['title']) ?></strong>
                    <span><?= e(DatePrecision::format($ev['start_date'], (string) $ev['date_precision'], $dateFormat)) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div class="page-head">
    <div>
        <p class="kicker">ARCHIVOS</p>
        <h1>Colecciones</h1>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/archivos/nuevo')) ?>"><?= icon('plus', 14) ?> Nuevo archivo</a>
</div>

<div class="card-grid">
    <?php foreach ($archives as $archive): ?>
        <a class="file-card" href="<?= e(url('/archivos/' . (int) $archive['id'])) ?>" style="--accent: <?= e((string) $archive['color']) ?>">
            <div class="file-card-ico"><?= icon((string) $archive['icon'], 24) ?></div>
            <h2><?= e((string) $archive['name']) ?></h2>
            <p class="muted"><?= e((string) ($archive['description'] ?? '')) ?></p>
            <p class="count-pill"><?= (int) $archive['timeline_count'] ?> líneas · <?= (int) $archive['event_count'] ?> eventos</p>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($ungrouped): ?>
    <h2 class="section-title">Sin archivo</h2>
    <ul class="plain">
        <?php foreach ($ungrouped as $tl): ?>
            <li><a href="<?= e(url('/lineas/' . (int) $tl['id'])) ?>"><?= e((string) $tl['name']) ?></a> · <?= (int) $tl['event_count'] ?> eventos</li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div class="page-head">
    <div>
        <p class="kicker">TIMEKEEPER</p>
        <h1>Mis archivos</h1>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/archivos/nuevo')) ?>"><?= icon('plus', 14) ?> Nuevo archivo</a>
</div>

<section class="archive-list">
    <?php foreach ($archives as $archive): ?>
        <a class="archive-row" href="<?= e(url('/archivos/' . (int) $archive['id'])) ?>">
            <span class="archive-ico" style="color: <?= e((string) $archive['color']) ?>"><?= icon((string) $archive['icon'], 22) ?></span>
            <span class="archive-meta">
                <strong><?= e((string) $archive['name']) ?></strong>
                <?php if ($archive['description']): ?><span class="muted"><?= e((string) $archive['description']) ?></span><?php endif; ?>
            </span>
            <span class="count-pill"><?= (int) $archive['timeline_count'] ?> líneas</span>
        </a>
    <?php endforeach; ?>

    <?php if ($ungrouped): ?>
        <a class="archive-row is-loose" href="<?= e(url('/lineas')) ?>">
            <span class="archive-ico"><?= icon('scroll', 22) ?></span>
            <span class="archive-meta">
                <strong>Sin archivo</strong>
                <span class="muted">Líneas independientes</span>
            </span>
            <span class="count-pill"><?= count($ungrouped) ?> líneas</span>
        </a>
    <?php endif; ?>

    <?php if (!$archives && !$ungrouped): ?>
        <p class="empty">El archivo está vacío. Creá el primero.</p>
    <?php endif; ?>
</section>

<section class="stat-bar">
    <span><?= (int) $stats['timelines'] ?> líneas</span>
    <span><?= (int) $stats['events'] ?> eventos</span>
    <span><?= (int) $stats['archives'] ?> archivos</span>
    <span><?= (int) $stats['tags'] ?> tags</span>
    <span><?= (int) $stats['milestones'] ?> hitos</span>
</section>

<div class="grid-2">
    <section class="panel">
        <h2>El archivo en números</h2>
        <ul class="plain">
            <li>Más grande: <?= $stats['largest'] ? e($stats['largest']['name'] . ' · ' . $stats['largest']['event_count'] . ' eventos') : '—' ?></li>
            <li>Más antigua: <?= $stats['oldest'] ? e($stats['oldest']['name']) : '—' ?></li>
            <li>Década con más eventos: <?= $stats['decade'] ? e('Década de ' . $stats['decade']['decade_year']) : '—' ?></li>
        </ul>
    </section>
    <section class="panel">
        <h2>Últimos eventos</h2>
        <?php if (!$recent): ?>
            <p class="muted">Todavía no hay movimientos.</p>
        <?php else: ?>
            <ul class="plain">
                <?php foreach ($recent as $ev): ?>
                    <li>
                        <a href="<?= e(url('/lineas/' . (int) $ev['timeline_id'] . '?evento=' . (int) $ev['id'])) ?>"><?= e((string) $ev['title']) ?></a>
                        <span class="muted"> · <?= e((string) $ev['timeline_name']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

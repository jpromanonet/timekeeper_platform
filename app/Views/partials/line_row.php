<?php
/** @var array $tl */
/** @var list<array> $series */
$series = $series ?? [];
?>
<div class="line-row<?= $series ? ' has-serie' : '' ?>">
    <label class="tick" title="Seleccionar">
        <input type="checkbox" name="ids[]" value="<?= (int) $tl['id'] ?>">
    </label>
    <a class="line-row-main" href="<?= e(url('/lineas/' . (int) $tl['id'])) ?>">
        <span class="dot" style="background: <?= e((string) $tl['color']) ?>"></span>
        <span>
            <strong><?= e((string) $tl['name']) ?></strong>
            <?php if ($tl['description']): ?><span class="muted block"><?= e((string) $tl['description']) ?></span><?php endif; ?>
        </span>
        <span class="muted">
            <?= (int) $tl['event_count'] ?> eventos
            <?php if (!empty($tl['first_event'])): ?>
                · <?= e(substr((string) $tl['first_event'], 0, 4)) ?> → <?= !empty($tl['last_event']) ? e(substr((string) $tl['last_event'], 0, 4)) : 'hoy' ?>
            <?php endif; ?>
        </span>
    </a>
    <?php if ($series): ?>
        <select
            class="serie-pick"
            name="series_id"
            form="move-serie-<?= (int) $tl['id'] ?>"
            onchange="this.form.submit()"
            aria-label="Serie"
            onclick="event.stopPropagation()"
        >
            <option value="">Sin serie</option>
            <?php foreach ($series as $opt): ?>
                <option value="<?= (int) $opt['id'] ?>" <?= ((int) ($tl['series_id'] ?? 0) === (int) $opt['id']) ? 'selected' : '' ?>>
                    <?= e((string) $opt['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
</div>

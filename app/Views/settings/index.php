<div class="page-head">
    <h1>Ajustes</h1>
</div>

<section class="panel">
    <h2>Perfil</h2>
    <form class="form-grid" method="post" action="<?= e(form_action()) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?= route_field('/ajustes/perfil') ?>
        <label>Nombre
            <input type="text" name="name" required maxlength="160" value="<?= e((string) ($account['name'] ?? '')) ?>">
        </label>
        <label>Correo
            <input type="email" name="email" required maxlength="190" value="<?= e((string) ($account['email'] ?? '')) ?>" autocomplete="email">
        </label>
        <label>Avatar
            <?php if (!empty($account['avatar'])): ?>
                <img class="media-preview media-preview-avatar" src="<?= e(media_url((string) $account['avatar'])) ?>" alt="Avatar actual">
            <?php endif; ?>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
        </label>
        <button class="btn btn-primary" type="submit">Guardar perfil</button>
    </form>
</section>

<section class="panel">
    <h2>Contraseña</h2>
    <form class="form-grid" method="post" action="<?= e(form_action()) ?>">
        <?= csrf_field() ?>
        <?= route_field('/ajustes/password') ?>
        <label>Actual <input type="password" name="current_password" required></label>
        <label>Nueva <input type="password" name="new_password" required minlength="8"></label>
        <label>Confirmar <input type="password" name="new_password_confirm" required minlength="8"></label>
        <button class="btn btn-primary" type="submit">Cambiar</button>
    </form>
</section>

<section class="panel">
    <h2>Preferencias de líneas</h2>
    <form class="form-grid" method="post" action="<?= e(form_action()) ?>">
        <?= csrf_field() ?>
        <?= route_field('/ajustes/preferencias') ?>
        <label>Vista predeterminada
            <select name="default_view">
                <option value="vertical" <?= ($prefs['default_view'] ?? '') === 'vertical' ? 'selected' : '' ?>>Vertical</option>
                <option value="horizontal" <?= ($prefs['default_view'] ?? '') === 'horizontal' ? 'selected' : '' ?>>Horizontal</option>
            </select>
        </label>
        <label>Densidad
            <select name="density">
                <option value="compact" <?= ($prefs['density'] ?? '') === 'compact' ? 'selected' : '' ?>>Compacta</option>
                <option value="comfortable" <?= ($prefs['density'] ?? '') === 'comfortable' ? 'selected' : '' ?>>Holgada</option>
            </select>
        </label>
        <label>Formato de fecha
            <select name="date_format">
                <option value="d/m/Y" <?= ($prefs['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                <option value="Y-m-d" <?= ($prefs['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                <option value="m/d/Y" <?= ($prefs['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
            </select>
        </label>
        <label>Tema
            <select name="theme">
                <?php foreach (themes() as $key => $lab): ?>
                    <option value="<?= e($key) ?>" <?= ($prefs['theme'] ?? 'archivist') === $key ? 'selected' : '' ?>><?= e($lab) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="check">
            <input type="checkbox" name="interface_sounds" value="1" <?= !empty($prefs['interface_sounds']) ? 'checked' : '' ?>>
            Sonidos de interfaz
        </label>
        <button class="btn btn-primary" type="submit">Guardar preferencias</button>
    </form>
</section>

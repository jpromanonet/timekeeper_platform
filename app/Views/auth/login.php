<?php /** @var string|null $error */ /** @var string|null $success */ ?>
<h2>Entrar a la colección</h2>
<?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="flash flash-ok"><?= e($success) ?></div><?php endif; ?>
<form method="post" action="<?= e(form_action()) ?>" class="stack">
    <?= csrf_field() ?>
    <?= route_field('/login') ?>
    <label>Correo
        <input type="email" name="email" required autocomplete="username">
    </label>
    <label>Contraseña
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="btn btn-primary" type="submit">Abrir colección</button>
</form>
<p class="auth-links">
    <a href="<?= e(url('/registro')) ?>">Crear colección</a>
    ·
    <a href="<?= e(url('/recuperar')) ?>">Recuperar contraseña</a>
</p>

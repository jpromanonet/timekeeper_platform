<?php /** @var string|null $error */ ?>
<h2>Crear archivo</h2>
<?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(form_action()) ?>" class="stack">
    <?= csrf_field() ?>
    <?= route_field('/registro') ?>
    <label>Nombre
        <input type="text" name="name" required maxlength="160">
    </label>
    <label>Correo
        <input type="email" name="email" required>
    </label>
    <label>Contraseña
        <input type="password" name="password" required minlength="8">
    </label>
    <label>Confirmar
        <input type="password" name="password_confirm" required minlength="8">
    </label>
    <button class="btn btn-primary" type="submit">Registrar</button>
</form>
<p class="auth-links"><a href="<?= e(url('/login')) ?>">Ya tengo archivo</a></p>

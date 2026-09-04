<?php /** @var string|null $error */ /** @var string|null $success */ /** @var string|null $resetUrl */ ?>
<h2>Recuperar contraseña</h2>
<?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="flash flash-ok"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($resetUrl)): ?>
    <p class="muted">Enlace local (válido 2 horas):</p>
    <p><a href="<?= e($resetUrl) ?>"><?= e($resetUrl) ?></a></p>
<?php endif; ?>
<form method="post" action="<?= e(form_action()) ?>" class="stack">
    <?= csrf_field() ?>
    <?= route_field('/recuperar') ?>
    <label>Correo
        <input type="email" name="email" required>
    </label>
    <button class="btn btn-primary" type="submit">Generar enlace</button>
</form>
<p class="auth-links"><a href="<?= e(url('/login')) ?>">Volver</a></p>

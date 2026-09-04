<?php /** @var string $token */ /** @var string|null $error */ ?>
<h2>Nueva contraseña</h2>
<?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(form_action()) ?>" class="stack">
    <?= csrf_field() ?>
    <?= route_field('/restablecer/' . $token) ?>
    <label>Contraseña
        <input type="password" name="password" required minlength="8">
    </label>
    <label>Confirmar
        <input type="password" name="password_confirm" required minlength="8">
    </label>
    <button class="btn btn-primary" type="submit">Guardar</button>
</form>

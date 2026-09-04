<?php

declare(strict_types=1);

final class AuthController
{
    public function showLogin(): void
    {
        Auth::guestOnly();
        view('auth/login', [
            'title' => 'Iniciar sesión',
            'error' => flash('error'),
            'success' => flash('success'),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        verify_csrf();
        Auth::guestOnly();

        $email = mb_strtolower(trim((string) input('email', '')));
        $password = (string) input('password', '');

        if ($email === '' || $password === '') {
            flash('error', 'Ingresá tu correo y contraseña.');
            redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            flash('error', Auth::isLoginLocked()
                ? 'Demasiados intentos. Esperá unos minutos.'
                : 'Credenciales incorrectas.');
            redirect('/login');
        }

        redirect('/');
    }

    public function showRegister(): void
    {
        Auth::guestOnly();
        view('auth/register', [
            'title' => 'Crear archivo',
            'error' => flash('error'),
        ], 'layouts/auth');
    }

    public function register(): void
    {
        verify_csrf();
        Auth::guestOnly();

        $name = trim((string) input('name', ''));
        $email = mb_strtolower(trim((string) input('email', '')));
        $password = (string) input('password', '');
        $confirm = (string) input('password_confirm', '');

        if ($name === '' || $email === '' || $password === '') {
            flash('error', 'Completá nombre, correo y contraseña.');
            redirect('/registro');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'El correo no es válido.');
            redirect('/registro');
        }
        if (strlen($password) < 8) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            redirect('/registro');
        }
        if ($password !== $confirm) {
            flash('error', 'Las contraseñas no coinciden.');
            redirect('/registro');
        }

        $dup = Database::pdo()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $dup->execute(['email' => $email]);
        if ($dup->fetch()) {
            flash('error', 'Ese correo ya tiene un archivo.');
            redirect('/registro');
        }

        $ins = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)'
        );
        $ins->execute([
            'name' => mb_substr($name, 0, 160),
            'email' => $email,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $id = (int) Database::pdo()->lastInsertId();
        PreferenceService::ensure($id);
        Auth::attempt($email, $password);
        redirect('/');
    }

    public function logout(): void
    {
        verify_csrf();
        Auth::logout();
        redirect('/login');
    }

    public function showForgot(): void
    {
        Auth::guestOnly();
        view('auth/forgot', [
            'title' => 'Recuperar contraseña',
            'error' => flash('error'),
            'success' => flash('success'),
            'resetUrl' => flash('reset_url'),
        ], 'layouts/auth');
    }

    public function forgot(): void
    {
        verify_csrf();
        Auth::guestOnly();

        $email = mb_strtolower(trim((string) input('email', '')));
        $stmt = Database::pdo()->prepare('SELECT id FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $userId = (int) ($stmt->fetchColumn() ?: 0);

        if ($userId > 0) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            Database::pdo()->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 2 HOUR))'
            )->execute(['uid' => $userId, 'hash' => $hash]);
            flash('reset_url', absolute_url('/restablecer/' . $token));
            flash('success', 'Generamos un enlace de restablecimiento. En este archivo local se muestra abajo.');
        } else {
            flash('success', 'Si el correo existe, vas a poder restablecer la clave.');
        }
        redirect('/recuperar');
    }

    public function showReset(string $token): void
    {
        Auth::guestOnly();
        $row = $this->validReset($token);
        if (!$row) {
            flash('error', 'El enlace no es válido o venció.');
            redirect('/recuperar');
        }
        view('auth/reset', [
            'title' => 'Nueva contraseña',
            'token' => $token,
            'error' => flash('error'),
        ], 'layouts/auth');
    }

    public function reset(string $token): void
    {
        verify_csrf();
        Auth::guestOnly();
        $row = $this->validReset($token);
        if (!$row) {
            flash('error', 'El enlace no es válido o venció.');
            redirect('/recuperar');
        }

        $password = (string) input('password', '');
        $confirm = (string) input('password_confirm', '');
        if (strlen($password) < 8 || $password !== $confirm) {
            flash('error', 'La contraseña debe tener 8 caracteres y coincidir.');
            redirect('/restablecer/' . $token);
        }

        Database::pdo()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
            ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $row['user_id']]);
        Database::pdo()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')
            ->execute(['id' => $row['id']]);

        flash('success', 'Contraseña actualizada. Ya podés entrar.');
        redirect('/login');
    }

    private function validReset(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

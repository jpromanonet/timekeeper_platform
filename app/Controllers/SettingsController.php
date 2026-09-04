<?php

declare(strict_types=1);

final class SettingsController
{
    public function index(): void
    {
        Auth::requireLogin();
        view('settings/index', [
            'title' => 'Ajustes',
            'currentNav' => 'settings',
            'account' => Auth::user(),
            'prefs' => Auth::prefs(),
        ]);
    }

    public function updateProfile(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $name = mb_substr(trim((string) input('name', '')), 0, 160);
        if ($name === '') {
            flash('error', 'El nombre no puede quedar vacío.');
            redirect('/ajustes');
        }
        $avatar = null;
        try {
            $avatar = UploadService::storeImage($_FILES['avatar'] ?? null, 'avatars', 'user-' . Auth::id());
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/ajustes');
        }
        if ($avatar) {
            Database::pdo()->prepare('UPDATE users SET name = :name, avatar = :avatar WHERE id = :id')
                ->execute(['name' => $name, 'avatar' => $avatar, 'id' => Auth::id()]);
        } else {
            Database::pdo()->prepare('UPDATE users SET name = :name WHERE id = :id')
                ->execute(['name' => $name, 'id' => Auth::id()]);
        }
        Auth::refresh();
        flash('success', 'Perfil actualizado.');
        redirect('/ajustes');
    }

    public function updatePassword(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $current = (string) input('current_password', '');
        $next = (string) input('new_password', '');
        $confirm = (string) input('new_password_confirm', '');

        $stmt = Database::pdo()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => Auth::id()]);
        $hash = (string) $stmt->fetchColumn();
        if (!password_verify($current, $hash)) {
            flash('error', 'La contraseña actual no es correcta.');
            redirect('/ajustes');
        }
        if (strlen($next) < 8 || $next !== $confirm) {
            flash('error', 'La nueva contraseña debe tener 8 caracteres y coincidir.');
            redirect('/ajustes');
        }
        Database::pdo()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
            ->execute(['hash' => password_hash($next, PASSWORD_DEFAULT), 'id' => Auth::id()]);
        flash('success', 'Contraseña cambiada.');
        redirect('/ajustes');
    }

    public function updatePreferences(): void
    {
        Auth::requireLogin();
        verify_csrf();
        PreferenceService::update(Auth::id(), [
            'default_view' => (string) input('default_view', 'vertical'),
            'density' => (string) input('density', 'compact'),
            'date_format' => (string) input('date_format', 'd/m/Y'),
            'theme' => (string) input('theme', 'archivist'),
            'interface_sounds' => (bool) input('interface_sounds'),
        ]);
        Auth::refresh();
        flash('success', 'Preferencias guardadas.');
        redirect('/ajustes');
    }
}

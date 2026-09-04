<?php

declare(strict_types=1);

final class UploadService
{
    /** @var list<string> */
    private const IMAGES = [
        'image/jpeg',
        'image/pjpeg',
        'image/jpg',
        'image/png',
        'image/x-png',
        'image/webp',
        'image/gif',
    ];

    public static function storeImage(?array $file, string $subdir, string $basename): ?string
    {
        if ($file === null || !isset($file['error'])) {
            return null;
        }
        $error = (int) $file['error'];
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($error));
        }
        if (!filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('El servidor no acepta subida de archivos.');
        }
        if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
            throw new RuntimeException('La imagen supera los 8 MB.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            throw new RuntimeException('No llegó el archivo al servidor.');
        }

        $mime = self::detectMime($tmp, (string) ($file['name'] ?? ''));
        if (!in_array($mime, self::IMAGES, true)) {
            throw new RuntimeException('Usá JPG, PNG, WebP o GIF.');
        }

        $ext = match ($mime) {
            'image/png', 'image/x-png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $dir = self::root() . '/storage/' . trim($subdir, '/');
        self::ensureDir($dir);

        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) ?: 'img';
        $name = $safe . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!self::placeFile($tmp, $dest)) {
            throw new RuntimeException('No se pudo guardar la imagen. Revisá permisos de storage/.');
        }
        @chmod($dest, 0664);

        return trim($subdir, '/') . '/' . $name;
    }

    public static function absolutePath(string $relative): ?string
    {
        $relative = str_replace('\\', '/', $relative);
        $relative = ltrim($relative, '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }
        $full = self::root() . '/storage/' . $relative;
        return is_file($full) ? $full : null;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear el directorio de almacenamiento.');
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0777);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('No hay permiso para guardar imágenes en storage/.');
        }
    }

    private static function placeFile(string $tmp, string $dest): bool
    {
        if (is_uploaded_file($tmp) && @move_uploaded_file($tmp, $dest) && is_file($dest)) {
            return true;
        }
        if (@copy($tmp, $dest) && is_file($dest) && filesize($dest) > 0) {
            @unlink($tmp);
            return true;
        }
        $raw = @file_get_contents($tmp);
        if ($raw !== false && @file_put_contents($dest, $raw) !== false && is_file($dest)) {
            @unlink($tmp);
            return true;
        }
        return false;
    }

    private static function detectMime(string $tmp, string $originalName): string
    {
        if (class_exists('finfo')) {
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if ($mime !== '' && $mime !== 'application/octet-stream') {
                return strtolower($mime);
            }
        }
        $info = @getimagesize($tmp);
        if (is_array($info) && !empty($info['mime'])) {
            return strtolower((string) $info['mime']);
        }
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            default => '',
        };
    }

    private static function errorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen es más grande de lo que acepta el servidor.',
            UPLOAD_ERR_PARTIAL => 'La imagen se subió a medias. Probá de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal de PHP.',
            UPLOAD_ERR_CANT_WRITE => 'PHP no pudo escribir el archivo temporal.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida.',
            default => 'No se pudo subir la imagen.',
        };
    }
}

<?php

declare(strict_types=1);

final class UploadService
{
    /** @var list<string> */
    private const IMAGES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public static function storeImage(?array $file, string $subdir, string $basename): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir el archivo.');
        }
        if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
            throw new RuntimeException('La imagen supera los 4 MB.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $tmp !== '' ? (string) $finfo->file($tmp) : '';
        if (!in_array($mime, self::IMAGES, true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $dir = dirname(__DIR__, 2) . '/storage/' . trim($subdir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear el directorio de almacenamiento.');
        }

        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        return trim($subdir, '/') . '/' . $name;
    }
}

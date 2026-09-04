<?php

declare(strict_types=1);

final class MediaController
{
    /** @var list<string> */
    private const FOLDERS = ['covers', 'events', 'avatars'];

    /** @var array<string,string> */
    private const MIMES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    public function show(string $folder, string $file): void
    {
        Auth::requireLogin();
        if (!in_array($folder, self::FOLDERS, true) || !preg_match('/^[a-zA-Z0-9._-]+$/', $file)) {
            http_response_code(404);
            exit;
        }
        $path = UploadService::absolutePath($folder . '/' . $file);
        if ($path === null) {
            http_response_code(404);
            exit;
        }
        $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        $mime = self::MIMES[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}

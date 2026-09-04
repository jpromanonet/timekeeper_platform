<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '/' || $script === '\\' || $script === '.') {
        $cached = '';
        return $cached;
    }
    $cached = rtrim($script, '/');
    return $cached;
}

function request_scheme(): string
{
    $forwarded = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($forwarded !== '') {
        $forwarded = explode(',', $forwarded)[0];
        $forwarded = strtolower(trim($forwarded));
        if ($forwarded === 'https' || $forwarded === 'http') {
            return $forwarded;
        }
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    return $https ? 'https' : 'http';
}

function request_host(): string
{
    $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
    if ($forwarded !== '') {
        return trim(explode(',', $forwarded)[0]);
    }
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    return $host !== '' ? $host : 'localhost';
}

function url(string $path = '/'): string
{
    $extraQuery = [];
    $hash = '';
    if (str_contains($path, '#')) {
        [$path, $hash] = explode('#', $path, 2);
        $hash = '#' . $hash;
    }
    if (str_contains($path, '?')) {
        [$path, $qs] = explode('?', $path, 2);
        parse_str($qs, $extraQuery);
    }

    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }

    $base = base_path();

    if (
        str_starts_with($path, '/assets/')
        || str_starts_with($path, '/storage/')
        || preg_match('#^/[^/]+\.php$#', $path) === 1
    ) {
        $suffix = $extraQuery ? ('?' . http_build_query($extraQuery)) : '';
        return $base . $path . $suffix . $hash;
    }

    $script = $base . '/index.php';
    $params = $extraQuery;
    if ($path !== '/') {
        $params = array_merge(['r' => $path], $params);
    }
    $suffix = $params ? ('?' . http_build_query($params)) : '';
    return $script . $suffix . $hash;
}

function absolute_url(string $path = '/'): string
{
    $relative = url($path);
    if (preg_match('#^https?://#i', $relative) === 1) {
        return $relative;
    }
    return request_scheme() . '://' . request_host() . $relative;
}

function redirect(string $path): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $target = url($path);
    if (!headers_sent()) {
        header('Location: ' . $target, true, 303);
    }

    $safe = e($target);
    $json = json_encode($target, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">';
    echo '<meta http-equiv="refresh" content="0;url=' . $safe . '">';
    echo '<script>location.replace(' . $json . ');</script>';
    echo '<title>Redirigiendo…</title></head><body>';
    echo '<p>Guardado. <a href="' . $safe . '">Continuar</a></p>';
    echo '</body></html>';
    exit;
}

function form_action(?string $route = null): string
{
    return base_path() . '/index.php';
}

function route_field(string $path): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '/' || $path === '//') {
        return '';
    }
    return '<input type="hidden" name="r" value="' . e($path) . '">';
}

function view(string $template, array $data = [], ?string $layout = 'layouts/main'): void
{
    extract($data, EXTR_SKIP);
    $appName = (string) app_config('name', 'Timekeeper');
    $user = Auth::user();
    $templateFile = dirname(__DIR__) . '/app/Views/' . $template . '.php';
    if (!is_file($templateFile)) {
        throw new RuntimeException('View not found: ' . $template);
    }
    if ($layout === null) {
        require $templateFile;
        return;
    }
    $layoutFile = dirname(__DIR__) . '/app/Views/' . $layout . '.php';
    require $layoutFile;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        if (wants_json_request()) {
            json_response(['ok' => false, 'error' => 'Token CSRF inválido. Recargá la página e intentá de nuevo.'], 419);
        }
        http_response_code(419);
        exit('Token CSRF inválido');
    }
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($msg) ? $msg : null;
}

function wants_json_request(): bool
{
    $xhr = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    return strcasecmp($xhr, 'XMLHttpRequest') === 0;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function null_if_blank(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    return $value === '' ? null : $value;
}

function int_or_null(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    return (int) $value;
}

function slugify(string $text): string
{
    $text = trim(mb_strtolower($text));
    $map = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? $text;
    $text = trim($text, '-');
    return $text !== '' ? $text : 'linea';
}

function nav_active(string $prefix, bool $exact = false): string
{
    $current = $_GET['r'] ?? '/';
    if ($current === '' || $current === false) {
        $current = '/';
    }
    $current = '/' . trim((string) $current, '/');
    if ($current === '//') {
        $current = '/';
    }
    if ($exact) {
        return $current === $prefix ? 'is-active' : '';
    }
    if ($prefix === '/') {
        return $current === '/' ? 'is-active' : '';
    }
    return str_starts_with($current, $prefix) ? 'is-active' : '';
}

function first_name(string $fullName): string
{
    $parts = preg_split('/\s+/', trim($fullName)) ?: [];
    return $parts[0] ?? $fullName;
}

function initials(string $fullName): string
{
    $parts = preg_split('/\s+/', trim($fullName)) ?: [];
    $a = mb_substr($parts[0] ?? 'T', 0, 1);
    $b = mb_substr($parts[1] ?? 'K', 0, 1);
    return mb_strtoupper($a . $b);
}

function archive_icons(): array
{
    return [
        'scroll' => 'Pergamino',
        'book' => 'Libro',
        'castle' => 'Castillo',
        'coin' => 'Moneda',
        'sword' => 'Espada',
        'shield' => 'Escudo',
        'hourglass' => 'Reloj de arena',
        'map' => 'Mapa',
        'chest' => 'Cofre',
        'star' => 'Estrella',
        'flame' => 'Llama',
        'crown' => 'Corona',
    ];
}

function icon(string $name, int $size = 18): string
{
    $paths = [
        'scroll' => '<path d="M7 4h9a2 2 0 0 1 2 2v13H9"/><path d="M7 4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h11"/><path d="M9 9h6M9 13h4"/>',
        'book' => '<path d="M5 5a2 2 0 0 1 2-2h11v16H7a2 2 0 0 0-2 2V5z"/><path d="M7 19a2 2 0 0 1 2-2h9"/>',
        'castle' => '<path d="M4 20V9l3 2 3-4 3 4 3-2v11z"/><path d="M9 20v-5h6v5"/>',
        'coin' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v8M9.5 10.5c.6-.7 1.5-1 2.5-1s2 .4 2.5 1M9.5 13.5c.6.7 1.5 1 2.5 1s2-.4 2.5-1"/>',
        'sword' => '<path d="M14 4l6 6M8 20l6-6M16 8l-9 9-3 1 1-3 9-9z"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/>',
        'hourglass' => '<path d="M6 4h12M6 20h12M7 4c0 4 5 6 5 8s-5 4-5 8M17 4c0 4-5 6-5 8s5 4 5 8"/>',
        'map' => '<path d="M4 6l5-2 6 2 5-2v14l-5 2-6-2-5 2V6z"/><path d="M9 4v14M15 6v14"/>',
        'chest' => '<rect x="4" y="10" width="16" height="9"/><path d="M4 10V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2M12 10v3"/>',
        'star' => '<path d="M12 3l2.4 5.4L20 9.2l-4 4.1.9 5.7L12 16.5 7.1 19l.9-5.7-4-4.1 5.6-.8z"/>',
        'flame' => '<path d="M12 3s4 4 4 8a4 4 0 0 1-8 0c0-2 2-4 2-6 2 2 3 3 3 5"/><path d="M10 16c.6 1.4 2.4 2 3.5 1"/>',
        'crown' => '<path d="M4 16l2-8 6 4 6-4 2 8H4z"/><path d="M5 16h14v2H5z"/>',
        'diamond' => '<path d="M12 4l8 8-8 8-8-8z"/>',
        'dashboard' => '<rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="4"/><rect x="13" y="10" width="7" height="10"/><rect x="4" y="13" width="7" height="7"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="M20 20l-4-4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 4v2M12 18v2M4.9 7.1l1.4 1.4M17.7 15.5l1.4 1.4M4 12h2M18 12h2M4.9 16.9l1.4-1.4M17.7 8.5l1.4-1.4"/>',
        'favorites' => '<path d="M12 4l2.2 5.1L20 10l-4 3.8.9 5.2L12 16.4 7.1 19l.9-5.2-4-3.8 5.8-.9z"/>',
        'trash' => '<path d="M5 7h14M9 7V5h6v2M8 7l1 13h6l1-13"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'logout' => '<path d="M10 6H6v12h4M10 12h9M16 8l4 4-4 4"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'milestone' => '<path d="M12 3l2 5 5 .5-4 3.5 1.4 5L12 14.5 7.6 17l1.4-5-4-3.5 5-.5z"/>',
        'link' => '<path d="M9 12a4 4 0 0 1 0-6l2-2a4 4 0 0 1 6 6l-1 1"/><path d="M15 12a4 4 0 0 1 0 6l-2 2a4 4 0 0 1-6-6l1-1"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5 20c1.5-3.5 4-5 7-5s5.5 1.5 7 5"/>',
        'chart' => '<path d="M4 20h16"/><path d="M6 16v-5M12 16V7M18 16v-8"/>',
    ];
    $d = $paths[$name] ?? $paths['diamond'];
    return '<svg class="px-icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">' . $d . '</svg>';
}

function pastel_colors(): array
{
    return [
        '#9FB59D' => 'Sage',
        '#91A7C4' => 'Dusty Blue',
        '#A99BC2' => 'Lavender',
        '#C3909B' => 'Dust Rose',
        '#C9B58A' => 'Sand',
        '#B78972' => 'Clay',
    ];
}

function themes(): array
{
    return [
        'archivist' => 'Archivist',
        'dungeon' => 'Dungeon',
        'forest' => 'Forest',
        'royal' => 'Royal',
        'arcane' => 'Arcane',
        'monastery' => 'Monastery',
    ];
}

function status_label(string $status): string
{
    return match ($status) {
        'active' => 'Activa',
        'archived' => 'Archivada',
        'draft' => 'Borrador',
        default => ucfirst($status),
    };
}

function media_url(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $relative = ltrim(str_replace('\\', '/', $path), '/');
    $absolute = dirname(__DIR__) . '/storage/' . $relative;
    $version = is_file($absolute) ? (string) filemtime($absolute) : (string) time();
    return url('/media/' . $relative . '?v=' . $version);
}

function current_route(): string
{
    $current = $_GET['r'] ?? '/';
    $current = '/' . trim((string) $current, '/');
    return $current === '//' ? '/' : $current;
}

/** @return list<int> */
function input_id_list(string $key = 'ids'): array
{
    $raw = $_POST[$key] ?? [];
    if (!is_array($raw)) {
        $raw = [$raw];
    }
    $ids = [];
    foreach ($raw as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function safe_return_path(string $default): string
{
    $back = (string) input('back', $default);
    if ($back === '/' || $back === '/archivos' || $back === '/lineas') {
        return $back;
    }
    if (preg_match('#^/(archivos|lineas)/\d+$#', explode('?', $back, 2)[0]) !== 1) {
        return $default;
    }
    if (!str_contains($back, '?')) {
        return $back;
    }
    parse_str((string) (explode('?', $back, 2)[1] ?? ''), $query);
    $allowed = [];
    $serie = (string) ($query['serie'] ?? '');
    if ($serie === 'none' || ctype_digit($serie)) {
        $allowed['serie'] = $serie;
    }
    $linea = (string) ($query['linea'] ?? '');
    if (ctype_digit($linea)) {
        $allowed['linea'] = $linea;
    }
    $path = explode('?', $back, 2)[0];
    return $allowed === [] ? $path : $path . '?' . http_build_query($allowed);
}

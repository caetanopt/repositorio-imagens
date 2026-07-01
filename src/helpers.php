<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower($value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            default            => $value,
        };
    }
}

if (!function_exists('slugify')) {
    function slugify(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $transliteration = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        $str = strtr($str, $transliteration);
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s_]+/', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        return trim($str, '-');
    }
}

if (!function_exists('uuid4')) {
    function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = $_SESSION['csrf_token'] ?? '';
        return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Reads a value flashed via Controller::setOld() for exactly one render
     * (e.g. to repopulate a form after a validation error), then discards
     * it — without this, a stale value keeps leaking into unrelated forms
     * on every later page load until something else happens to overwrite it.
     */
    function old(string $key, mixed $default = ''): mixed
    {
        if (!array_key_exists($key, $_SESSION['old'] ?? [])) {
            return $default;
        }
        $value = $_SESSION['old'][$key];
        unset($_SESSION['old'][$key]);
        return $value;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('storagePath')) {
    function storagePath(string $path = ''): string
    {
        $base = env('STORAGE_PATH', __DIR__ . '/../storage/images');
        return rtrim($base, '/') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

class StorageResolver
{
    /**
     * Resolves an image file path to an absolute path on disk.
     * Tries the path as-is, then under storageBase/subDir/basename,
     * then under storageBase/basename. Returns the original value if nothing found.
     */
    public static function resolvePath(string $filePath, string $storageBase, string $subDir = ''): string
    {
        if (file_exists($filePath)) {
            return $filePath;
        }

        $base = rtrim($storageBase, '/');

        if ($subDir !== '') {
            $candidate = $base . '/' . $subDir . '/' . basename($filePath);
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $candidate = $base . '/' . ltrim(basename($filePath), '/');
        if (file_exists($candidate)) {
            return $candidate;
        }

        return $filePath;
    }

    /**
     * Resolves a stored file path or URL to a public URL.
     * If the path is already an HTTP URL, returns it unchanged.
     */
    public static function resolveUrl(string $path, string $appBase): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return $path !== '' ? rtrim($appBase, '/') . '/' . basename($path) : '';
    }
}

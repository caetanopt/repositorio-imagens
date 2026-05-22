<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

class StorageController extends Controller
{
    public function serve(Request $request, array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        $file = $params['file'] ?? '';

        // Basic path safety: no directory traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug) || strpbrk($file, '/\\') !== false) {
            http_response_code(400);
            exit;
        }

        $storagePath = rtrim(env('STORAGE_PATH', dirname(__DIR__, 2) . '/storage/images'), '/');
        $fullPath    = $storagePath . '/' . $slug . '/' . basename($file);

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            exit;
        }

        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($fullPath);
        exit;
    }
}

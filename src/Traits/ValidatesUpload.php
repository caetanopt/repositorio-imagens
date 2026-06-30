<?php

declare(strict_types=1);

namespace App\Traits;

trait ValidatesUpload
{
    private const UPLOAD_ALLOWED_MIMES = [
        'image/jpeg', 'image/jpg', 'image/png',
        'image/gif',  'image/webp', 'image/bmp',
    ];

    private const UPLOAD_MAGIC_SIGNATURES = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/jpg'  => ["\xFF\xD8\xFF"],
        'image/png'  => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'image/gif'  => ["\x47\x49\x46\x38\x37\x61", "\x47\x49\x46\x38\x39\x61"],
        'image/webp' => ["\x52\x49\x46\x46"],
        'image/bmp'  => ["\x42\x4D"],
    ];

    /**
     * Validates MIME type and magic bytes of an uploaded file.
     * Calls $this->json() with an error response and exits on failure.
     * Returns the detected MIME type on success.
     */
    protected function validateUploadedFileType(string $tmpPath): string
    {
        $mime = mime_content_type($tmpPath);

        if (!in_array($mime, self::UPLOAD_ALLOWED_MIMES, true)) {
            $this->json(['success' => false, 'error' => 'Tipo de ficheiro não suportado. Permitido: JPG, PNG, GIF, WEBP.'], 422);
        }

        $handle     = fopen($tmpPath, 'rb');
        $fileHeader = fread($handle, 12);
        fclose($handle);

        foreach (self::UPLOAD_MAGIC_SIGNATURES[$mime] ?? [] as $sig) {
            if (str_starts_with($fileHeader, $sig)) {
                return $mime;
            }
        }

        $this->json(['success' => false, 'error' => 'O ficheiro não é uma imagem válida.'], 422);
    }

    protected function validateUploadedFileSize(int $fileSize, int $maxMb): void
    {
        if ($fileSize > $maxMb * 1024 * 1024) {
            $this->json(['success' => false, 'error' => "Ficheiro demasiado grande. Máximo: {$maxMb} MB."], 422);
        }
    }
}

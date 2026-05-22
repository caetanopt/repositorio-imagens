<?php
declare(strict_types=1);
namespace App\Services;

class SupabaseStorage
{
    private string $url;
    private string $key;
    private string $bucket;

    public function __construct()
    {
        // Strip any path — only scheme+host needed (e.g. https://xxx.supabase.co)
        $raw = rtrim((string) env('SUPABASE_URL', ''), '/');
        $parsed = parse_url($raw);
        $this->url = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $this->key    = (string) env('SUPABASE_SERVICE_ROLE_KEY', '');
        $this->bucket = (string) env('SUPABASE_STORAGE_BUCKET', 'images');
    }

    /**
     * Create a signed URL so the browser can upload directly to Supabase.
     * Returns ['signed_url' => '...', 'public_url' => '...']
     */
    public function createSignedUploadUrl(string $storagePath): array
    {
        $endpoint = $this->url . '/storage/v1/object/upload/sign/' . $this->bucket . '/' . ltrim($storagePath, '/');

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '{}',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->key,
                'apikey: ' . $this->key,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Signed URL creation failed (' . $status . '): ' . $response);
        }

        $data = json_decode($response, true);
        return [
            'signed_url' => $this->url . ($data['url'] ?? ''),
            'public_url' => $this->publicUrl($storagePath),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->url !== '' && $this->key !== '';
    }

    /**
     * Upload a local file to Supabase Storage.
     * Returns the public URL.
     */
    public function upload(string $localPath, string $storagePath, string $mime): string
    {
        $endpoint = $this->url . '/storage/v1/object/' . $this->bucket . '/' . ltrim($storagePath, '/');

        $fileSize = filesize($localPath);
        $fh       = fopen($localPath, 'rb');
        if (!$fh) {
            throw new \RuntimeException('Cannot open file for upload: ' . $localPath);
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PUT            => true,
            CURLOPT_INFILE         => $fh,
            CURLOPT_INFILESIZE     => $fileSize,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->key,
                'apikey: ' . $this->key,
                'Content-Type: ' . $mime,
                'x-upsert: true',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if ($error) {
            throw new \RuntimeException('Supabase Storage cURL error: ' . $error);
        }

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Supabase Storage upload failed (' . $status . '): ' . $response);
        }

        return $this->publicUrl($storagePath);
    }

    /**
     * Delete files from Supabase Storage by path list.
     */
    public function delete(array $storagePaths): void
    {
        if (empty($storagePaths)) {
            return;
        }

        $endpoint = $this->url . '/storage/v1/object/' . $this->bucket;

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_POSTFIELDS     => json_encode(['prefixes' => array_map(fn($p) => ltrim($p, '/'), $storagePaths)]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->key,
                'apikey: ' . $this->key,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    public function publicUrl(string $storagePath): string
    {
        return $this->url . '/storage/v1/object/public/' . $this->bucket . '/' . ltrim($storagePath, '/');
    }

    /**
     * Extract the storage path from a public URL.
     */
    public function pathFromUrl(string $publicUrl): string
    {
        $prefix = $this->url . '/storage/v1/object/public/' . $this->bucket . '/';
        return str_starts_with($publicUrl, $prefix)
            ? substr($publicUrl, strlen($prefix))
            : basename($publicUrl);
    }
}

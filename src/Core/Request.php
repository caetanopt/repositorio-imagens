<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $server;
    private array $cookies;

    public function __construct()
    {
        $this->get     = $_GET    ?? [];
        $this->post    = $_POST   ?? [];
        $this->files   = $_FILES  ?? [];
        $this->server  = $_SERVER ?? [];
        $this->cookies = $_COOKIE ?? [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function isPost(): bool
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public function isGet(): bool
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || $this->get('format') === 'json';
    }

    public function ip(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        foreach ($headers as $h) {
            if (!empty($this->server[$h])) {
                $ip = explode(',', $this->server[$h])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        // Strip query string
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return '/' . trim($uri, '/');
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function getBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    public function jsonBody(): ?array
    {
        $body = $this->getBody();
        if (empty($body)) {
            return null;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }
}

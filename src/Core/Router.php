<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $namespace = 'App\\Controllers\\';

    public function get(string $pattern, string $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, string $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, string $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $request = new Request();
        $method  = $request->method();
        $uri     = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $uri);
            if ($params !== null) {
                $this->handle($route['handler'], $params, $request);
                return;
            }
        }

        // 404
        http_response_code(404);
        $this->render404();
    }

    private function match(string $pattern, string $uri): ?array
    {
        // Convert :param to named capture groups
        $regex = preg_replace_callback('/:([a-zA-Z_]+)/', function ($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return null;
    }

    private function handle(string $handler, array $params, Request $request): void
    {
        [$controllerName, $method] = explode('@', $handler);
        $class = $this->namespace . $controllerName;

        if (!class_exists($class)) {
            error_log("Controller not found: {$class}");
            http_response_code(500);
            die('Internal Server Error');
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            error_log("Method not found: {$class}::{$method}");
            http_response_code(500);
            die('Internal Server Error');
        }

        $controller->$method($request, $params);
    }

    private function render404(): void
    {
        $viewFile = __DIR__ . '/../Views/errors/404.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>';
            echo '<h1>404 — Página não encontrada</h1>';
            echo '<p><a href="/">Voltar ao início</a></p>';
            echo '</body></html>';
        }
    }
}

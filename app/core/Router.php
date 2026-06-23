<?php

declare(strict_types=1);

class Router
{
    /**
     * @var array<string, array<string, callable|array{0: string, 1: string}>>
     */
    private array $routes = [];

    public function get(string $path, callable|array $action): void
    {
        $this->addRoute('GET', $path, $action);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');
        $action = $this->routes[$method][$path] ?? null;

        if ($action === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (is_array($action)) {
            [$controllerClass, $controllerMethod] = $action;
            $controller = new $controllerClass();
            $controller->{$controllerMethod}();
            return;
        }

        $action();
    }

    private function addRoute(string $method, string $path, callable|array $action): void
    {
        $this->routes[$method][$this->normalizePath($path)] = $action;
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . trim($path, '/');
    }
}

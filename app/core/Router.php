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

    public function post(string $path, callable|array $action): void
    {
        $this->addRoute('POST', $path, $action);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');
        $action = $this->routes[$method][$path] ?? null;

        if ($action !== null) {
            $this->invoke($action);
            return;
        }

        foreach ($this->routes[$method] ?? [] as $routePath => $routeAction) {
            if (!str_contains($routePath, '{')) {
                continue;
            }

            $params = $this->matchDynamicRoute($routePath, $path);

            if ($params !== null) {
                $this->invoke($routeAction, $params);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
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

    /**
     * @return array<int, string>|null
     */
    private function matchDynamicRoute(string $routePath, string $requestPath): ?array
    {
        $segments = explode('/', trim($routePath, '/'));
        $regexSegments = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{[a-zA-Z_][a-zA-Z0-9_]*\}$/', $segment) === 1) {
                $regexSegments[] = '([^\/]+)';
                continue;
            }

            $regexSegments[] = preg_quote($segment, '/');
        }

        $patternBody = $regexSegments === [] ? '' : '\/' . implode('\/', $regexSegments);
        $pattern = '/^' . $patternBody . '$/';

        if (!preg_match($pattern, $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);

        return array_map('rawurldecode', array_values($matches));
    }

    /**
     * @param array<int, string> $params
     */
    private function invoke(callable|array $action, array $params = []): void
    {
        if (is_array($action)) {
            [$controllerClass, $controllerMethod] = $action;
            $controller = new $controllerClass();
            $controller->{$controllerMethod}(...$params);
            return;
        }

        $action(...$params);
    }
}

<?php

declare(strict_types=1);

abstract class Controller
{
    protected function render(string $view, array $data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        View::render($view, $data);
    }
}

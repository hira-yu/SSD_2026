<?php

declare(strict_types=1);

class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewFile = base_path('app/Views/' . str_replace('.', '/', $view) . '.php');

        if (!is_file($viewFile)) {
            throw new RuntimeException('View file not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        require base_path('app/Views/layouts/header.php');
        require $viewFile;
        require base_path('app/Views/layouts/footer.php');
    }
}

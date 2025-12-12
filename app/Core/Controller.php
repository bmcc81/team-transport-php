<?php
namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        if ($view === '') {
            throw new \InvalidArgumentException('View name cannot be empty');
        }

        extract($data);

        $path = __DIR__ . "/../../views/{$view}.php";

        if (!file_exists($path)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        require $path;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function back(): void
    {
        $target = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $target);
        exit;
    }
}

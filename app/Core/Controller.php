<?php
namespace App\Core;

class Controller
{
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
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

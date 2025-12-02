<?php
namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        $viewPath = __DIR__ . '/../../views/' . $template . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View not found: " . htmlspecialchars($template);
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }
}

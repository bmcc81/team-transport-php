<?php
namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        // Convert route patterns into regex
        $pattern = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";

        $this->routes[$method][] = [
            'pattern'   => $pattern,
            'handler'   => $handler,
            'middleware'=> $middleware,
            'raw_path'  => $path
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $method = strtoupper($method);

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {

                // Extract params (only named ones)
                $params = [];
                foreach ($matches as $key => $val) {
                    if (!is_int($key)) {
                        $params[$key] = $val;
                    }
                }

                // Middleware
                foreach ($route['middleware'] as $middleware) {
                    if (is_object($middleware) && method_exists($middleware, 'handle')) {
                        $middleware->handle();
                    }
                }

                // Determine controller + method
                [$controllerName, $methodName] = explode('@', $route['handler']);
                $controllerClass = 'App\\Controllers\\' . $controllerName;

                // Admin protection
                if (str_starts_with($controllerName, 'Admin\\')) {
                    // session_start();
                    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                        http_response_code(403);
                        include __DIR__ . '/../../views/errors/403.php';
                        return;
                    }
                }

                if (!class_exists($controllerClass)) {
                    http_response_code(500);
                    echo "Controller not found: $controllerClass";
                    return;
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $methodName)) {
                    http_response_code(500);
                    echo "Method not found: $methodName";
                    return;
                }

                // Pass parameters
                $controller->$methodName(...array_values($params));
                return;
            }
        }

        // No match
        http_response_code(404);
        echo "404 Not Found";
    }
}

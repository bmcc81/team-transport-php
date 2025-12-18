<?php
namespace App\Core;

use App\Middleware\MiddlewareKernel;

class Router
{
    private array $routes = [];
    private array $currentGroup = [
        'prefix' => '',
        'middleware' => []
    ];

    /**
     * Normalize URI — strip query + trailing slash
     */
    private function normalize(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }

    /**
     * GET / POST helpers
     */
    public function get(string $pattern, string $handler, array $middleware = []): void
    {
        $this->register('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, string $handler, array $middleware = []): void
    {
        $this->register('POST', $pattern, $handler, $middleware);
    }

    /**
     * Route groups — like Laravel
     */
    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previous = $this->currentGroup;

        $this->currentGroup = [
            'prefix' => $previous['prefix'] . $prefix,
            'middleware' => array_merge($previous['middleware'], $middleware)
        ];

        $callback($this);

        $this->currentGroup = $previous;
    }

    /**
     * Register a route
     */
    private function register(string $method, string $pattern, string $handler, array $middleware): void
    {
        $pattern = $this->currentGroup['prefix'] . $pattern;
        $pattern = $this->normalize($pattern);

        $isDynamic = str_contains($pattern, '{');

        $combinedMiddleware = array_merge($this->currentGroup['middleware'], $middleware);

        $route = [
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $combinedMiddleware,
            'dynamic'    => $isDynamic
        ];

        if ($isDynamic) {
            $this->routes[$method]['dynamic'][] = $route;
        } else {
            $this->routes[$method]['static'][$pattern] = $route;
        }
    }

    /**
     * Dispatcher
     */
    public function dispatch(string $uri, string $httpMethod): void
    {
        $uri = $this->normalize($uri);
        $method = strtoupper($httpMethod);

        $static  = $this->routes[$method]['static']  ?? [];
        $dynamic = $this->routes[$method]['dynamic'] ?? [];

        if (isset($static[$uri])) {
            $this->execute($static[$uri]);
            return;
        }

        foreach ($dynamic as $route) {
            $regex = preg_replace('#\{[^/]+\}#', '([^/]+)', $route['pattern']);
            $regex = "#^{$regex}$#";

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                $this->execute($route, $matches);
                return;
            }
        }

        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
    }

    /**
     * Execute the route using the middleware pipeline
     */
    private function execute(array $route, array $params = []): void
    {
        
        $kernel = new MiddlewareKernel();
        $middlewareList = $kernel->resolve($route['middleware']);

        $controllerHandler = function ($request) use ($route, $params) {
            [$controller, $method] = explode('@', $route['handler'], 2);

            // ✅ If already fully-qualified (App\... or \App\...), do NOT prefix again
            $controller = ltrim($controller, '\\');

            if (str_starts_with($controller, 'App\\')) {
                $controllerClass = $controller;
            } else {
                // ✅ Supports: "DashboardController" and "Admin\\UserController"
                $controllerClass = "App\\Controllers\\{$controller}";
            }

            if (!class_exists($controllerClass)) {
                http_response_code(500);
                echo "Controller class not found: " . htmlspecialchars($controllerClass);
                return;
            }

            $instance = new $controllerClass();

            if (!method_exists($instance, $method)) {
                http_response_code(500);
                echo "Controller method not found: " . htmlspecialchars($controllerClass . '@' . $method);
                return;
            }

            call_user_func_array([$instance, $method], $params);
            
        };

        $pipeline = array_reduce(
            array_reverse($middlewareList),
            function ($next, $mwClass) {
                return function ($request) use ($next, $mwClass) {
                    $mw = new $mwClass();
                    return $mw->handle($request, $next);
                };
            },
            $controllerHandler
        );

        $pipeline($_REQUEST);
    }
}

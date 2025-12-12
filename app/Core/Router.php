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

        // Apply new group prefix & middleware
        $this->currentGroup = [
            'prefix' => $previous['prefix'] . $prefix,
            'middleware' => array_merge($previous['middleware'], $middleware)
        ];

        $callback($this);

        // Restore previous group after callback
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

        // Static match
        if (isset($static[$uri])) {
            $this->execute($static[$uri]);
            return;
        }

        // Dynamic match
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

        // Handler
        $controllerHandler = function($request) use ($route, $params) {
            [$controller, $method] = explode('@', $route['handler']);
            $controllerClass = "App\\Controllers\\" . $controller;
            $instance = new $controllerClass();

            call_user_func_array([$instance, $method], $params);
        };

        // Pipeline
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

<?php
namespace App\Middleware;

class MiddlewareKernel
{
    /**
     * Middleware aliases — used by routes
     */
    public static array $aliases = [
        'auth'   => \App\Middleware\AuthMiddleware::class,
        'guest'  => \App\Middleware\GuestMiddleware::class,
        'driver' => \App\Middleware\DriverOnlyMiddleware::class,
        'admin'  => \App\Middleware\AdminOnlyMiddleware::class,
    ];

    /**
     * Middleware groups (optional)
     */
    public static array $groups = [
        'web' => ['auth'],
        'driver-zone' => ['auth', 'driver'],
    ];

    /**
     * Resolve middleware aliases + groups into class names
     */
    public function resolve(array $mw): array
    {
        $resolved = [];

        foreach ($mw as $item) {
            if (isset(self::$aliases[$item])) {
                $resolved[] = self::$aliases[$item];
            } elseif (isset(self::$groups[$item])) {
                foreach (self::$groups[$item] as $alias) {
                    $resolved[] = self::$aliases[$alias];
                }
            } else {
                $resolved[] = $item; // direct class name
            }
        }

        return $resolved;
    }
}

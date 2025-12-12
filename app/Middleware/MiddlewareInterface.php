<?php
namespace App\Middleware;

interface MiddlewareInterface
{
    public function handle($request, callable $next);
}

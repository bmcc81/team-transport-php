<?php

namespace App\Middleware;

class GuestMiddleware
{
    public function handle(): bool
    {
        // Allow all guest routes to load without redirect logic
        return true;
    }
}

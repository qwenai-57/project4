<?php

declare(strict_types=1);

/**
 * Base Middleware - Parent class for all middlewares
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Middlewares;

abstract class Middleware
{
    /**
     * Handle the middleware
     *
     * @param callable $next
     * @return void
     */
    abstract public function handle(callable $next): void;
}

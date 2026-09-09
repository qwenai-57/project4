<?php

declare(strict_types=1);

/**
 * CSRF Middleware - Verify CSRF token on POST requests
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Middlewares;

class CsrfMiddleware extends Middleware
{
    /**
     * Handle CSRF verification
     *
     * @param callable $next
     * @return void
     */
    public function handle(callable $next): void
    {
        // Only check on state-changing methods
        if ($this->isStateChangingRequest()) {
            if (!verify_csrf()) {
                if ($this->isApiRequest()) {
                    json_response([
                        'success' => false,
                        'message' => 'CSRF token mismatch'
                    ], 403);
                }

                abort(403, 'CSRF token mismatch');
            }
        }

        $next();
    }

    /**
     * Check if request is state-changing (POST, PUT, DELETE, PATCH)
     *
     * @return bool
     */
    protected function isStateChangingRequest(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true);
    }

    /**
     * Check if request is API request
     *
     * @return bool
     */
    protected function isApiRequest(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT']) 
            && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
    }
}

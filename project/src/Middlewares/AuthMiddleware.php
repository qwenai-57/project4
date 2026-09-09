<?php

declare(strict_types=1);

/**
 * Auth Middleware - Check if user is authenticated
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Middlewares;

use App\Support\Database\DB;

class AuthMiddleware extends Middleware
{
    protected DB $db;

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    /**
     * Handle authentication check
     *
     * @param callable $next
     * @return void
     */
    public function handle(callable $next): void
    {
        if (!$this->isAuthenticated()) {
            // For API requests
            if ($this->isApiRequest()) {
                json_response([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // For web requests
            redirect('/login');
        }

        $next();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        $sessionId = $_COOKIE['session_id'] ?? null;

        if (!$sessionId) {
            return false;
        }

        $session = $this->db->table('sessions')
            ->where('id', $sessionId)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return false;
        }

        // Check if session is expired
        if (strtotime($session->expires_at) < time()) {
            return false;
        }

        // Update last active time
        $this->db->table('sessions')
            ->where('id', $sessionId)
            ->update(['last_active_at' => now()]);

        return true;
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

<?php

declare(strict_types=1);

/**
 * Login Controller - Handle user authentication
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Controllers\Api\Auth;

use App\Controllers\Controller;
use App\Support\Database\DB;
use App\Support\Str;

class LoginController extends Controller
{
    protected DB $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = DB::getInstance();
    }

    /**
     * Display login form
     *
     * @return void
     */
    public function showLoginForm(): void
    {
        if ($this->isAuthenticated()) {
            redirect('/dashboard');
        }

        $this->with('title', 'Login')
             ->view('auth/login');
    }

    /**
     * Handle login request
     *
     * @return void
     */
    public function login(): void
    {
        // Verify CSRF token
        if (!verify_csrf()) {
            $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ], 403);
        }

        // Validate input
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:3']
        ]);

        if ($validated === false) {
            $errors = $this->getValidationErrors();
            $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
        }

        $email = (string) input('email');
        $password = (string) input('password');
        $remember = (bool) input('remember', false);

        // Find user by email
        $user = $this->db->table('users')
            ->where('email', $email)
            ->where('status', 'active')
            ->first();

        if (!$user || !password_verify($password, $user->password)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create session
        $sessionId = $this->createSession($user);

        // Set session cookie
        $this->setSessionCookie($sessionId, $remember);

        // Log successful login
        log_info('User logged in', ['user_id' => $user->id, 'email' => $user->email]);

        $this->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->full_name
                ],
                'redirect' => '/dashboard'
            ]
        ]);
    }

    /**
     * Handle logout
     *
     * @return void
     */
    public function logout(): void
    {
        $sessionId = $_COOKIE['session_id'] ?? null;

        if ($sessionId) {
            $this->db->table('sessions')
                ->where('id', $sessionId)
                ->update(['status' => 'expired']);

            setcookie('session_id', '', time() - 3600, '/', '', true, true);
        }

        session_destroy();

        log_info('User logged out');

        $this->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Create session record
     *
     * @param object $user
     * @return string
     */
    protected function createSession(object $user): string
    {
        $sessionId = Str::uuid();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Generate signature for session validation
        $signature = hash_hmac('sha256', $sessionId . $user->id, config('app.key', ''));

        $this->db->table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'signature' => $signature,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $sessionId;
    }

    /**
     * Set session cookie securely
     *
     * @param string $sessionId
     * @param bool $remember
     * @return void
     */
    protected function setSessionCookie(string $sessionId, bool $remember): void
    {
        $lifetime = $remember ? 30 * 24 * 60 * 60 : 7 * 24 * 60 * 60; // 30 days or 7 days
        
        setcookie(
            'session_id',
            $sessionId,
            [
                'expires' => time() + $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => true, // HTTPS only
                'httponly' => true, // Not accessible via JavaScript
                'samesite' => 'Strict'
            ]
        );
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
     * Get current authenticated user
     *
     * @return object|null
     */
    public function getCurrentUser(): ?object
    {
        $sessionId = $_COOKIE['session_id'] ?? null;

        if (!$sessionId) {
            return null;
        }

        $session = $this->db->table('sessions')
            ->select('s.*', 'u.id', 'u.email', 'u.full_name', 'u.phone')
            ->join('users u', 's.user_id', '=', 'u.id')
            ->where('s.id', $sessionId)
            ->where('s.status', 'active')
            ->first();

        return $session ?: null;
    }
}

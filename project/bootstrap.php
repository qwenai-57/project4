<?php

declare(strict_types=1);

/**
 * Bootstrap File - Initialize the framework
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

// Error reporting for production (disabled in production)
if (config('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set default timezone
date_default_timezone_set(config('app.timezone', 'UTC'));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Load helper functions
require_once __DIR__ . '/src/Helpers/functions.php';

// Autoloader for PSR-4 style class loading
spl_autoload_register(function (string $class): void {
    // Project namespace prefix
    $prefix = 'App\\';
    
    // Base directory for the namespace prefix
    $baseDir = __DIR__ . '/src/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Set content type header
header('Content-Type: text/html; charset=utf-8');

/**
 * Simple Router - Route requests to controllers
 */
class Router
{
    protected static array<string, callable> $routes = [];
    protected static array<string> $middlewareGroups = [];

    /**
     * Register a GET route
     *
     * @param string $path
     * @param callable|string $handler
     * @return void
     */
    public static function get(string $path, callable|string $handler): void
    {
        self::$routes['GET'][$path] = $handler;
    }

    /**
     * Register a POST route
     *
     * @param string $path
     * @param callable|string $handler
     * @return void
     */
    public static function post(string $path, callable|string $handler): void
    {
        self::$routes['POST'][$path] = $handler;
    }

    /**
     * Register a route with middleware
     *
     * @param array<string> $middleware
     * @param callable $callback
     * @return void
     */
    public static function middleware(array $middleware, callable $callback): void
    {
        self::$middlewareGroups = $middleware;
        $callback();
        self::$middlewareGroups = [];
    }

    /**
     * Dispatch the request
     *
     * @return void
     */
    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Remove trailing slash except for root
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        // Find matching route
        if (!isset(self::$routes[$method][$uri])) {
            abort(404, 'Page not found');
        }

        $handler = self::$routes[$method][$uri];

        // Execute middleware chain
        $middlewareIndex = 0;
        $next = function () use (&$middlewareIndex, $handler, &$next): void {
            if ($middlewareIndex < count(self::$middlewareGroups)) {
                $middlewareClass = self::$middlewareGroups[$middlewareIndex];
                $middlewareIndex++;
                
                $middleware = new $middlewareClass();
                $middleware->handle($next);
            } else {
                // Execute handler
                if (is_callable($handler)) {
                    call_user_func($handler);
                } elseif (is_string($handler) && str_contains($handler, '@')) {
                    [$controller, $action] = explode('@', $handler);
                    $instance = new $controller();
                    call_user_func([$instance, $action]);
                }
            }
        };

        $next();
    }
}

// Define common routes
Router::get('/', 'App\Controllers\HomeController@index');
Router::get('/about', 'App\Controllers\HomeController@about');

// Auth routes
Router::get('/login', 'App\Controllers\Api\Auth\LoginController@showLoginForm');
Router::post('/login', 'App\Controllers\Api\Auth\LoginController@login');
Router::post('/logout', 'App\Controllers\Api\Auth\LoginController@logout');

// Protected routes example
Router::middleware(['App\Middlewares\AuthMiddleware'], function (): void {
    Router::get('/dashboard', function (): void {
        echo '<h1>Dashboard</h1><p>Welcome to your dashboard!</p>';
    });
});

// Run the router
Router::dispatch();

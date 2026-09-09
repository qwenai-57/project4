<?php

declare(strict_types=1);

/**
 * Configuration File - Database and application settings
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 * 
 * IMPORTANT: Table prefix changed from wk_ to hos_
 */

return [
    /**
     * Application Settings
     */
    'app' => [
        'name' => env('APP_NAME', 'Project Komplace'),
        'env' => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'url' => env('APP_URL', 'https://localhost'),
        'key' => env('APP_KEY', ''), // Required for CSRF and session security
        'timezone' => 'UTC',
    ],

    /**
     * Database Configuration
     * Table prefix: hos_ (changed from wk_)
     */
    'database' => [
        'driver' => env('DB_CONNECTION', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'project_komplace'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'hos_', // Changed from wk_ to hos_
        'strict' => true,
        'engine' => 'InnoDB',
    ],

    /**
     * Session Configuration
     */
    'session' => [
        'driver' => 'database',
        'table' => 'hos_sessions', // Changed from wk_sessions
        'lifetime' => 10080, // 7 days in minutes
        'expire_on_close' => false,
        'secure' => true,
        'http_only' => true,
        'same_site' => 'Strict',
    ],

    /**
     * Mail Configuration
     */
    'mail' => [
        'default' => env('MAIL_DRIVER', 'smtp'),
        'smtp' => [
            'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => 30,
        ],
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Project Komplace'),
        ],
    ],

    /**
     * API Configuration
     */
    'api' => [
        'rate_limit' => 60, // requests per minute
        'throttle_enabled' => true,
    ],

    /**
     * Security Settings
     */
    'security' => [
        'csrf_enabled' => true,
        'xss_protection' => true,
        'frame_options' => 'DENY',
        'content_type_sniffing' => false,
    ],
];

<?php

declare(strict_types=1);

/**
 * Helper Functions - Global helper functions for the framework
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

if (!function_exists('app_path')) {
    /**
     * Get the path to the app directory
     *
     * @param string $path
     * @return string
     */
    function app_path(string $path = ''): string
    {
        return dirname(__DIR__) . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base directory
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return dirname(__DIR__, 2) . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;
        
        if ($config === null) {
            $configFile = base_path('config.php');
            $config = file_exists($configFile) ? require $configFile : [];
        }

        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => preg_replace_callback('/^([\'"])(.*)\1$/', fn($m) => $m[2], $value),
        };
    }
}

if (!function_exists('request')) {
    /**
     * Get request input
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function request(string $key = '', mixed $default = null): mixed
    {
        if ($key === '') {
            return $_REQUEST;
        }

        return $_REQUEST[$key] ?? $default;
    }
}

if (!function_exists('input')) {
    /**
     * Get POST/GET input with sanitization
     *
     * @param string $key
     * @param mixed $default
     * @param int $filter
     * @return mixed
     */
    function input(string $key, mixed $default = null, int $filter = FILTER_DEFAULT): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        
        if ($value === null) {
            return $default;
        }

        return filter_var($value, $filter);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to URL
     *
     * @param string $url
     * @param int $statusCode
     * @return never
     */
    function redirect(string $url, int $statusCode = 302): never
    {
        header("Location: $url", true, $statusCode);
        exit;
    }
}

if (!function_exists('json_response')) {
    /**
     * Return JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @param int $options
     * @return never
     */
    function json_response(mixed $data, int $statusCode = 200, int $options = 0): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, $options | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('abort')) {
    /**
     * Abort with HTTP error
     *
     * @param int $code
     * @param string $message
     * @return never
     */
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message ?: "HTTP Error $code";
        exit;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Generate CSRF token
     *
     * @return string
     */
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate CSRF hidden input field
     *
     * @return string
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Verify CSRF token
     *
     * @param string|null $token
     * @return bool
     */
    function verify_csrf(?string $token = null): bool
    {
        $token = $token ?? ($_POST['_csrf_token'] ?? '');
        
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize output
     *
     * @param string $value
     * @return string
     */
    function sanitize(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('now')) {
    /**
     * Get current datetime
     *
     * @return string
     */
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today')) {
    /**
     * Get current date
     *
     * @return string
     */
    function today(): string
    {
        return date('Y-m-d');
    }
}

if (!function_exists('uuid')) {
    /**
     * Generate UUID
     *
     * @return string
     */
    function uuid(): string
    {
        return \App\Support\Str::uuid();
    }
}

if (!function_exists('random_string')) {
    /**
     * Generate random string
     *
     * @param int $length
     * @return string
     */
    function random_string(int $length = 16): string
    {
        return \App\Support\Str::random($length);
    }
}

if (!function_exists('db')) {
    /**
     * Get database instance
     *
     * @return \App\Support\Database\DB
     */
    function db(): \App\Support\Database\DB
    {
        return \App\Support\Database\DB::getInstance();
    }
}

if (!function_exists('setting')) {
    /**
     * Get setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Support\Setting::get($key, $default);
    }
}

if (!function_exists('mailer')) {
    /**
     * Get mailer instance
     *
     * @return \App\Support\Mail\Mailer
     */
    function mailer(): \App\Support\Mail\Mailer
    {
        return new \App\Support\Mail\Mailer(new \App\Support\Mail\SMTP());
    }
}

if (!function_exists('collection')) {
    /**
     * Create collection
     *
     * @param array<int, mixed> $items
     * @return \App\Support\Collection
     */
    function collection(array $items = []): \App\Support\Collection
    {
        return new \App\Support\Collection($items);
    }
}

if (!function_exists('logger')) {
    /**
     * Simple logger
     *
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    function logger(string $level, string $message, array $context = []): void
    {
        $logFile = base_path('logs/app.log');
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message";
        
        if (!empty($context)) {
            $formattedMessage .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        file_put_contents($logFile, $formattedMessage . PHP_EOL, FILE_APPEND);
    }
}

if (!function_exists('log_info')) {
    /**
     * Log info message
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    function log_info(string $message, array $context = []): void
    {
        logger('INFO', $message, $context);
    }
}

if (!function_exists('log_error')) {
    /**
     * Log error message
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    function log_error(string $message, array $context = []): void
    {
        logger('ERROR', $message, $context);
    }
}

if (!function_exists('log_warning')) {
    /**
     * Log warning message
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    function log_warning(string $message, array $context = []): void
    {
        logger('WARNING', $message, $context);
    }
}

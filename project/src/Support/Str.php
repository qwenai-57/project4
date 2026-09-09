<?php

declare(strict_types=1);

/**
 * String Helper Class - Lightweight string manipulation utilities
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support;

class Str
{
    /**
     * Generate a random string
     *
     * @param int $length
     * @return string
     */
    public static function random(int $length = 16): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)))[0][$length];
    }

    /**
     * Generate UUID v4
     *
     * @return string
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return sprintf('%08s-%04s-%04s-%04s-%012s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }

    /**
     * Convert string to slug
     *
     * @param string $value
     * @return string
     */
    public static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
        $value = preg_replace('/[\s-]+/', '-', $value);
        return trim($value, '-');
    }

    /**
     * Check if string starts with given prefix
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Check if string ends with given suffix
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Check if string contains substring
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Limit string length
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit)) . $end;
    }

    /**
     * Convert to camelCase
     *
     * @param string $value
     * @return string
     */
    public static function camel(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', lcfirst($value));
    }

    /**
     * Convert to StudlyCase
     *
     * @param string $value
     * @return string
     */
    public static function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    /**
     * Convert to snake_case
     *
     * @param string $value
     * @return string
     */
    public static function snake(string $value): string
    {
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1_', $value);
        return mb_strtolower($value);
    }

    /**
     * Convert to kebab-case
     *
     * @param string $value
     * @return string
     */
    public static function kebab(string $value): string
    {
        return self::snake($value);
    }

    /**
     * Check if string is empty or whitespace
     *
     * @param string|null $value
     * @return bool
     */
    public static function isEmpty(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }

    /**
     * Check if string is not empty
     *
     * @param string|null $value
     * @return bool
     */
    public static function isNotEmpty(?string $value): bool
    {
        return !self::isEmpty($value);
    }

    /**
     * Sanitize string for HTML output
     *
     * @param string $value
     * @param int $flags
     * @return string
     */
    public static function sanitize(string $value, int $flags = ENT_QUOTES | ENT_HTML5): string
    {
        return htmlspecialchars($value, $flags, 'UTF-8');
    }

    /**
     * Hash a string using secure algorithm
     *
     * @param string $value
     * @return string
     */
    public static function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Compare two strings securely (timing-safe)
     *
     * @param string $known
     * @param string $user
     * @return bool
     */
    public static function equals(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * Strip all tags from string
     *
     * @param string $value
     * @param string $allowedTags
     * @return string
     */
    public static function stripTags(string $value, string $allowedTags = ''): string
    {
        return strip_tags($value, $allowedTags);
    }

    /**
     * Remove extra whitespace
     *
     * @param string $value
     * @return string
     */
    public static function squashSpaces(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value));
    }

    /**
     * Parse email to extract name and email
     *
     * @param string $email
     * @return array{name: string, email: string}
     */
    public static function parseEmail(string $email): array
    {
        if (preg_match('/^(.*?)\s*<([^>]+)>$/', $email, $matches)) {
            return [
                'name' => trim($matches[1]),
                'email' => trim($matches[2])
            ];
        }

        return [
            'name' => '',
            'email' => $email
        ];
    }
}

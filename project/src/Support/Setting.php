<?php

declare(strict_types=1);

/**
 * Settings Class - Retrieve settings from database
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support;

use App\Support\Database\DB;

class Setting
{
    /**
     * @var array<string, mixed>|null
     */
    protected static ?array $cachedSettings = null;

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::loadSettings();

        if (array_key_exists($key, $settings)) {
            return self::castValue($settings[$key]['setting_value'], $settings[$key]['data_type']);
        }

        return $default;
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $dataType
     * @param string $title
     * @param string|null $description
     * @param int $autoload
     * @param mixed $defaultValue
     * @return bool
     */
    public static function set(
        string $key,
        mixed $value,
        string $dataType = 'string',
        string $title = '',
        ?string $description = null,
        int $autoload = 0,
        mixed $defaultValue = null
    ): bool {
        $db = DB::getInstance();
        $encodedValue = self::encodeValue($value, $dataType);

        // Check if setting exists
        $existing = $db->table('hos_settings')->where('setting_key', $key)->first();

        if ($existing) {
            return (bool) $db->table('hos_settings')
                ->where('id', $existing->id)
                ->update([
                    'setting_value' => $encodedValue,
                    'data_type' => $dataType,
                    'title' => $title ?: $existing->title,
                    'description' => $description ?? $existing->description,
                    'autoload' => $autoload,
                    'default_value' => self::encodeValue($defaultValue, $dataType),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }

        return (bool) $db->table('hos_settings')->insert([
            'setting_key' => $key,
            'setting_value' => $encodedValue,
            'data_type' => $dataType,
            'title' => $title,
            'description' => $description,
            'autoload' => $autoload,
            'default_value' => self::encodeValue($defaultValue, $dataType),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get all autoload settings
     *
     * @return array<string, mixed>
     */
    public static function getAutoload(): array
    {
        $db = DB::getInstance();
        $results = $db->table('hos_settings')
            ->where('autoload', 1)
            ->get();

        $settings = [];
        foreach ($results as $row) {
            $settings[$row->setting_key] = self::castValue($row->setting_value, $row->data_type);
        }

        return $settings;
    }

    /**
     * Clear cached settings
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cachedSettings = null;
    }

    /**
     * Load all settings
     *
     * @return array<string, array{setting_value: string, data_type: string}>
     */
    protected static function loadSettings(): array
    {
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        $db = DB::getInstance();
        $results = $db->table('hos_settings')->get();

        self::$cachedSettings = [];
        foreach ($results as $row) {
            self::$cachedSettings[$row->setting_key] = [
                'setting_value' => $row->setting_value,
                'data_type' => $row->data_type
            ];
        }

        return self::$cachedSettings;
    }

    /**
     * Cast value to appropriate type
     *
     * @param string $value
     * @param string $dataType
     * @return mixed
     */
    protected static function castValue(string $value, string $dataType): mixed
    {
        return match ($dataType) {
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'boolean', 'bool' => (bool) $value,
            'json' => json_decode($value, true) ?? null,
            'null' => null,
            default => $value,
        };
    }

    /**
     * Encode value for storage
     *
     * @param mixed $value
     * @param string $dataType
     * @return string
     */
    protected static function encodeValue(mixed $value, string $dataType): string
    {
        return match ($dataType) {
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'boolean', 'bool' => $value ? '1' : '0',
            'null' => '',
            default => (string) $value,
        };
    }
}

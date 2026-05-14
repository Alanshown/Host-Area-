<?php

namespace App\Support;

class ProjectEnv
{
    private static ?array $values = null;

    public static function get(string $key, $default = null)
    {
        $values = static::values();

        if (! array_key_exists($key, $values)) {
            return $default;
        }

        return static::normalizeValue($values[$key]);
    }

    private static function values(): array
    {
        if (static::$values !== null) {
            return static::$values;
        }

        $path = base_path('.env');
        if (! is_file($path)) {
            return static::$values = [];
        }

        $parsed = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($trimmed, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $trimmed, 2);
            $name = trim($name);
            $value = trim($value);

            if ($value !== '' && ($value[0] === '"' || $value[0] === '\'')) {
                $quote = $value[0];
                if (substr($value, -1) === $quote) {
                    $value = substr($value, 1, -1);
                }
            }

            $parsed[$name] = str_replace(['\\n', '\\r'], ["\n", "\r"], $value);
        }

        return static::$values = $parsed;
    }

    private static function normalizeValue(string $value)
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}
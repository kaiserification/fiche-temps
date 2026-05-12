<?php

namespace App\Support;

class AppSettings
{
    private static string $path = '';

    private static function filePath(): string
    {
        if (self::$path === '') {
            self::$path = storage_path('app/settings.json');
        }

        return self::$path;
    }

    public static function all(): array
    {
        $path = self::filePath();

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $settings = self::all();
        $settings[$key] = $value;

        file_put_contents(self::filePath(), json_encode($settings, JSON_PRETTY_PRINT));
    }

    public static function merge(array $data): void
    {
        $settings = array_merge(self::all(), $data);

        file_put_contents(self::filePath(), json_encode($settings, JSON_PRETTY_PRINT));
    }
}

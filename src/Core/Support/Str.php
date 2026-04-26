<?php

declare(strict_types=1);

namespace App\Core\Support;

final class Str
{
    private function __construct() {}

    /**
     * Trims whitespace and returns null if the result is empty.
     */
    public static function trimOrNull(string $value): ?string
    {
        return mb_trim($value) ?: null;
    }

    /**
     * Reads $key from $data, casts to string, trims, and returns null if absent or empty.
     * Handy for DTO::fromArray() to avoid the verbose isset+trim+empty dance.
     */
    public static function trimOrNullFromArray(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        return self::trimOrNull((string) $data[$key]);
    }

    /**
     * Reads $key from $data, casts to string, trims, and returns $default if absent.
     * Use when the DTO field is required (non-nullable) and validation is enforced downstream.
     */
    public static function trimFromArray(array $data, string $key, string $default = ''): string
    {
        return mb_trim((string) ($data[$key] ?? $default));
    }
}

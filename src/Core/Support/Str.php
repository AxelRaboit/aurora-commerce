<?php

declare(strict_types=1);

namespace Aurora\Core\Support;

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

    /**
     * Reads $key from $data and returns the email lowercased + trimmed, or '' when absent.
     * Format validation is left to the downstream Symfony Assert\Email constraint.
     */
    public static function emailFromArray(array $data, string $key): string
    {
        return mb_strtolower(self::trimFromArray($data, $key));
    }

    /**
     * Reads $key from $data and returns the email lowercased + trimmed, or null when empty/absent.
     */
    public static function emailOrNullFromArray(array $data, string $key): ?string
    {
        $email = self::trimOrNullFromArray($data, $key);

        return null === $email ? null : mb_strtolower($email);
    }
}

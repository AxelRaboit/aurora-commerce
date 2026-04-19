<?php

declare(strict_types=1);

namespace App\Support;

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
}

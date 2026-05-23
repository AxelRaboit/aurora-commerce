<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Support;

/**
 * Normalises a transaction description into the canonical lookup
 * pattern used by PersonalFinanceCategorizationRule:
 *   - strip accents via iconv ASCII//TRANSLIT
 *   - lowercase
 *   - collapse whitespace
 *
 * Identical normalisation must apply to both write (learn) and read
 * (suggest) paths — extracted to a single helper to keep them in sync.
 *
 * Returns null when the result is empty (no rule should ever be
 * keyed on an empty string).
 */
final class PatternNormalizer
{
    private function __construct() {}

    public static function normalize(?string $description): ?string
    {
        if (null === $description || '' === $description) {
            return null;
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $description);
        $ascii = is_string($ascii) ? $ascii : $description;

        $pattern = mb_strtolower(mb_trim($ascii));
        $pattern = (string) preg_replace('/\s+/u', ' ', $pattern);

        return '' === $pattern ? null : $pattern;
    }
}

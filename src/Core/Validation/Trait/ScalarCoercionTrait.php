<?php

declare(strict_types=1);

namespace Aurora\Core\Validation\Trait;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

use function is_int;
use function is_scalar;
use function is_string;

/**
 * Tiny coercion helpers for unsafe payloads (JSON bodies, OCR drafts, query
 * strings…). Keeps controllers/services free of repetitive defensive casts.
 *
 * - stringOrNull: trims to a non-empty string or null.
 * - intOrNull: integer or null; throws on non-numeric.
 * - dateOrNull: DateTimeImmutable from any parseable string; throws on invalid.
 *
 * The throwing variants surface translation keys so JSON failure responses can
 * map them straight through with no message rewriting.
 */
trait ScalarCoercionTrait
{
    private function stringOrNull(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $trimmed = mb_trim((string) $value);

        return '' === $trimmed ? null : $trimmed;
    }

    /** @throws InvalidArgumentException with translation key */
    private function intOrNull(mixed $value, string $errorKey = 'shared.validation.notNumeric'): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException($errorKey);
        }

        return (int) $value;
    }

    /** Lossless int coercion that returns null on failure (no throw). */
    private function intOrNullSafe(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /** @throws InvalidArgumentException with translation key */
    private function dateOrNull(mixed $value, string $errorKey = 'shared.validation.invalidDate'): ?DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException($errorKey);
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            throw new InvalidArgumentException($errorKey);
        }
    }

    /** Permissive variant for OCR drafts where the model may emit junk. */
    private function dateOrNullSafe(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}

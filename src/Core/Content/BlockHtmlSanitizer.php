<?php

declare(strict_types=1);

namespace Aurora\Core\Content;

/**
 * Sanitizes the light HTML Editor.js allows in text fields (b, i, a, code, …):
 * removes script/style/iframe entirely and strips every tag outside a short
 * whitelist. Shared by the core block renderer and any module-contributed
 * block renderer, so the rendering rules stay identical everywhere.
 */
final readonly class BlockHtmlSanitizer
{
    public function safe(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        // Remove script/style/iframe tags entirely
        $value = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', $value) ?? '';

        // Strip all tags except a short whitelist
        $allowed = '<a><b><strong><i><em><u><s><br><code><mark>';

        return strip_tags($value, $allowed);
    }
}

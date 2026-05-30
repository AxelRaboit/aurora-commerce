<?php

declare(strict_types=1);

namespace Aurora\Core\Search;

final class SearchSnippetBuilder
{
    /**
     * Extracts a text snippet around the first matching search token.
     * Falls back to the beginning of the content if no token matches.
     */
    public function build(?string $content, string $query, int $radius = 60): string
    {
        if (null === $content || '' === $content) {
            return '';
        }

        $lowerContent = mb_strtolower($content);
        $lowerQuery = mb_strtolower(preg_replace('/["\-+]/', '', $query) ?? '');
        $tokens = array_values(array_filter(explode(' ', $lowerQuery), static fn (string $token): bool => '' !== $token));

        foreach ($tokens as $token) {
            $position = mb_strpos($lowerContent, $token);
            if (false !== $position) {
                $start = max(0, $position - $radius);
                $length = mb_strlen($token) + $radius * 2;
                $snippet = mb_substr($content, $start, $length);
                if ($start > 0) {
                    $snippet = '…'.$snippet;
                }

                if ($start + $length < mb_strlen($content)) {
                    $snippet .= '…';
                }

                return $snippet;
            }
        }

        return mb_substr($content, 0, $radius * 2).(mb_strlen($content) > $radius * 2 ? '…' : '');
    }
}

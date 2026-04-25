<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Theme;
use App\Repository\MediaRepository;
use App\Repository\ThemeRepository;

final class ThemeContext
{
    private ?Theme $cachedTheme = null;

    private bool $resolved = false;

    public function __construct(
        private readonly ThemeRepository $themeRepository,
        private readonly MediaRepository $mediaRepository,
    ) {}

    public function activeTheme(): ?Theme
    {
        if (!$this->resolved) {
            $this->cachedTheme = $this->themeRepository->findActive();
            $this->resolved = true;
        }

        return $this->cachedTheme;
    }

    public function activeThemeSlug(): string
    {
        return $this->activeTheme()?->getSlug() ?? 'default';
    }

    public function headerLogoUrl(): ?string
    {
        $rawId = $this->activeTheme()?->getConfig()['header_logo_media_id'] ?? '';
        if (!is_string($rawId) || '' === $rawId) {
            return null;
        }

        $media = $this->mediaRepository->find((int) $rawId);

        return $media?->getPublicUrl();
    }

    public function headerCustomText(): ?string
    {
        $text = $this->activeTheme()?->getConfig()['header_custom_text'] ?? '';

        return (is_string($text) && '' !== $text) ? $text : null;
    }

    public function footerText(string $siteName): string
    {
        $custom = $this->activeTheme()?->getConfig()['footer_text'] ?? '';
        $text = (is_string($custom) && '' !== $custom) ? $custom : '© {year} {siteName}';

        return str_replace(['{year}', '{siteName}'], [date('Y'), $siteName], $text);
    }

    public function cssVariableOverrides(): string
    {
        $config = $this->activeTheme()?->getConfig() ?? [];
        if ([] === $config) {
            return '';
        }

        $parts = [];
        foreach ($config as $key => $value) {
            if (is_string($key) && is_string($value) && str_starts_with($key, '--')) {
                $parts[] = $key.': '.$value.';';
            }
        }

        return implode(' ', $parts);
    }
}

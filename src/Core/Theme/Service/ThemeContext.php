<?php

declare(strict_types=1);

namespace App\Core\Theme\Service;

use App\Core\Media\Repository\MediaRepository;
use App\Core\Theme\Entity\Theme;
use App\Core\Theme\Repository\ThemeRepository;

final class ThemeContext
{
    /** Default primary colour seed when the active theme has none configured (Tailwind indigo-500). */
    public const string DEFAULT_PRIMARY_COLOR = '#6366f1';

    private ?Theme $cachedTheme = null;

    private bool $resolved = false;

    public function __construct(
        private readonly ThemeRepository $themeRepository,
        private readonly MediaRepository $mediaRepository,
        private readonly PrimaryColorPalette $primaryColorPalette,
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

    /** Active theme's primary colour as hex (default: indigo-500). */
    public function primaryColor(): string
    {
        $value = $this->activeTheme()?->getConfig()['primary_color'] ?? '';

        return is_string($value) && '' !== $value ? $value : self::DEFAULT_PRIMARY_COLOR;
    }

    /**
     * Generates the CSS that overrides Tailwind's default --color-indigo-* scale with
     * the active theme's primary colour. Output goes inside a <style> in the layout
     * head so every Tailwind class like `bg-indigo-600` automatically uses the new hue.
     */
    public function primaryColorCss(): string
    {
        $palette = $this->primaryColorPalette->generate($this->primaryColor());
        $declarations = [];
        foreach ($palette as $stop => $value) {
            $declarations[] = sprintf('--color-indigo-%s: %s;', $stop, $value);
        }

        return ':root{'.implode('', $declarations).'}';
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

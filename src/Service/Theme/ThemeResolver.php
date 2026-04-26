<?php

declare(strict_types=1);

namespace App\Service\Theme;

final readonly class ThemeResolver
{
    public function __construct(
        private ThemeContext $themeContext,
        private string $projectDir,
    ) {}

    public function resolve(string $templateName): string
    {
        $activeSlug = $this->themeContext->activeThemeSlug();
        $activePath = sprintf('%s/templates/front/themes/%s/%s.html.twig', $this->projectDir, $activeSlug, $templateName);

        if ('default' !== $activeSlug && file_exists($activePath)) {
            return sprintf('front/themes/%s/%s.html.twig', $activeSlug, $templateName);
        }

        return sprintf('front/themes/default/%s.html.twig', $templateName);
    }

    /** @return array<string, string> */
    public function resolveAll(): array
    {
        $templates = ['layout', 'home', 'post', 'archive', 'term', '_post_card', '_pagination'];
        $map = [];
        foreach ($templates as $name) {
            $map[$name] = $this->resolve($name);
        }

        return $map;
    }
}

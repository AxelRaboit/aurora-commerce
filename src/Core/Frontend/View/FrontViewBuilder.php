<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\View;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeContext;

/**
 * Provides the base template variables required by the default front layout.
 * Inject this in any custom front controller or view builder.
 */
final readonly class FrontViewBuilder
{
    public function __construct(
        private FrontContext $frontContext,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    /**
     * @param bool $showFrontMenus Pass true only for fronts that have registered menu locations (e.g. Editorial).
     *
     * @return array<string, mixed>
     */
    public function baseView(string $locale, string $pageDescription = '', array $alternates = [], bool $showFrontMenus = false): array
    {
        return [
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
            'pageDescription' => $pageDescription ?: ($this->frontContext->siteDescription() ?? ''),
            'alternates' => $alternates,
            'showFrontMenus' => $showFrontMenus,
        ];
    }
}

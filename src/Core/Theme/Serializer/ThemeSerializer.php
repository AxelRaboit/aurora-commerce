<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\Serializer;

use Aurora\Core\Theme\Entity\ThemeInterface;
use Aurora\Core\Theme\Manager\ThemeManager;

final readonly class ThemeSerializer
{
    public function __construct(private ThemeManager $themeManager) {}

    /** @return array<string, mixed> */
    public function serialize(ThemeInterface $theme): array
    {
        return [
            'id' => $theme->getId(),
            'slug' => $theme->getSlug(),
            'name' => $theme->getName(),
            'description' => $theme->getDescription(),
            'active' => $theme->isActive(),
            'config' => $theme->getConfig(),
            'templateCount' => $this->themeManager->countTemplates($theme->getSlug()),
        ];
    }
}

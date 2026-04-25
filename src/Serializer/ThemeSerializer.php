<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Theme;
use App\Manager\ThemeManager;

final readonly class ThemeSerializer
{
    public function __construct(private ThemeManager $themeManager) {}

    /** @return array<string, mixed> */
    public function serialize(Theme $theme): array
    {
        return [
            'id'            => $theme->getId(),
            'slug'          => $theme->getSlug(),
            'name'          => $theme->getName(),
            'description'   => $theme->getDescription(),
            'active'        => $theme->isActive(),
            'config'        => $theme->getConfig(),
            'templateCount' => $this->themeManager->countTemplates($theme->getSlug()),
        ];
    }
}

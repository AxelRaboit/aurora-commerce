<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Theme\Serializer;

use Aurora\Module\Configuration\Theme\Entity\ThemeInterface;
use Aurora\Module\Configuration\Theme\Manager\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ThemeSerializerInterface::class)]
class ThemeSerializer implements ThemeSerializerInterface
{
    public function __construct(protected readonly ThemeManagerInterface $themeManager) {}

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

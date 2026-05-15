<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Aurora\Core\Module\Service\ModuleRegistry;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Twig\Attribute\AsTwigFunction;

final readonly class SidemenuExtension
{
    public function __construct(
        private ModuleRegistry $moduleRegistry,
        private SettingRepository $settingRepository,
    ) {}

    #[AsTwigFunction(name: 'sidemenu_nav_sections')]
    public function getSidemenuNavSections(): array
    {
        return $this->moduleRegistry->getNavSections();
    }

    #[AsTwigFunction(name: 'nav_section_aliases')]
    public function getNavSectionAliases(): array
    {
        $json = $this->settingRepository->get(ApplicationParameterEnum::NavSectionAliases->value, '{}');
        $decoded = json_decode($json ?? '{}', true);

        return is_array($decoded) ? $decoded : [];
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavSection;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;

final readonly class ModulesViewBuilder
{
    /** @param iterable<ModuleInterface> $modules */
    public function __construct(
        private SettingRepository $settingRepository,
        private iterable $modules,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function modulesPayload(): array
    {
        $catalogByModuleId = $this->buildCatalogByModuleId();
        $parameters = [];

        foreach (ApplicationParameterEnum::cases() as $parameter) {
            if ('modules' !== $parameter->getGroup()) {
                continue;
            }

            $moduleId = $parameter->getModuleId();
            $navItems = [];

            if (null !== $moduleId && isset($catalogByModuleId[$moduleId])) {
                foreach ($catalogByModuleId[$moduleId] as $section) {
                    foreach ($section->items as $item) {
                        $navItems[] = ['labelKey' => $item->labelKey, 'icon' => $item->icon];
                    }
                }
            }

            $parameters[] = [
                'key' => $parameter->getKey(),
                'label' => $parameter->getLabel(),
                'description' => $parameter->getDescription(),
                'value' => $this->settingRepository->get($parameter->getKey(), $parameter->getDefaultValue()),
                'requires' => $parameter->getCascadeRequires(),
                'navItems' => $navItems,
            ];
        }

        return ['parameters' => $parameters];
    }

    /** @return array<string, NavSection[]> */
    private function buildCatalogByModuleId(): array
    {
        $catalog = [];
        foreach ($this->modules as $module) {
            $catalog[$module->getId()] = $module->getCatalogNavSections();
        }

        return $catalog;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload): array
    {
        return [
            'tab' => 'modules',
            'modules' => $payload,
        ];
    }
}

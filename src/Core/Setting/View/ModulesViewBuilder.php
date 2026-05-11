<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\View;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Enum\ModuleToggleTypeEnum;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ModulesViewBuilder
{
    /** @param iterable<ModuleInterface> $modules */
    public function __construct(
        private SettingRepository $settingRepository,
        private iterable $modules,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function modulesPayload(): array
    {
        $catalogByModuleId = $this->buildCatalogByModuleId();
        $parameters = [];

        foreach (ModuleParameterEnum::cases() as $parameter) {
            if ($parameter->getParentCase() instanceof ModuleParameterEnum) {
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

            $subModules = [];
            foreach (ModuleParameterEnum::cases() as $subParameter) {
                if ($subParameter->getParentCase() !== $parameter) {
                    continue;
                }

                $subModules[] = [
                    'key' => $subParameter->getKey(),
                    'label' => $this->translator->trans($subParameter->getLabel()),
                    'description' => $this->translator->trans($subParameter->getDescription()),
                    'value' => $this->settingRepository->get($subParameter->getKey(), $subParameter->getDefaultValue()),
                    'requires' => $subParameter->getCascadeRequires(),
                    'type' => ModuleToggleTypeEnum::fromKey($subParameter->getKey())->value,
                ];
            }

            $parameters[] = [
                'key' => $parameter->getKey(),
                'label' => $this->translator->trans($parameter->getLabel()),
                'description' => $this->translator->trans($parameter->getDescription()),
                'value' => $this->settingRepository->get($parameter->getKey(), $parameter->getDefaultValue()),
                'requires' => $parameter->getCascadeRequires(),
                'navItems' => $navItems,
                'subModules' => $subModules,
                'type' => ModuleToggleTypeEnum::fromKey($parameter->getKey())->value,
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

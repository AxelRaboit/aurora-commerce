<?php

declare(strict_types=1);

namespace Aurora\Core\Configuration\Setting\View;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Enum\ModuleToggleTypeEnum;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Core\Module\Toggle\ModuleToggleRegistry;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ModulesViewBuilder
{
    /** @param iterable<ModuleInterface> $modules */
    public function __construct(
        private SettingRepository $settingRepository,
        private iterable $modules,
        private TranslatorInterface $translator,
        private ModuleToggleRegistry $moduleToggleRegistry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function modulesPayload(): array
    {
        $catalogByModuleId = $this->buildCatalogByModuleId();
        $parameters = [];

        // 1) Core toggles — enum-driven, preserving the (display parent =
        // getParentCase) vs (cascade dependency = getCascadeRequires) split.
        // Cross-module deps like Billing→Crm stay at top-level via getParentCase
        // null, but `requires` still exposes the cascade key for the UI.
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

        // 2) Client toggles — toggles declared by aurora-client modules via
        // ModuleToggleProviderInterface but absent from ModuleParameterEnum
        // (e.g. `app_tracking_*`). They are grouped by top-level toggle with
        // their direct children as sub-modules. Display order is appended
        // after core toggles.
        $coreKeys = array_map(static fn (ModuleParameterEnum $case): string => $case->value, ModuleParameterEnum::cases());
        $allToggles = $this->moduleToggleRegistry->getAll();

        $clientToggles = array_filter(
            $allToggles,
            static fn (ModuleToggle $toggle): bool => !in_array($toggle->key, $coreKeys, true),
        );

        foreach ($clientToggles as $toggle) {
            if (!$toggle->isTopLevel()) {
                continue;
            }

            $moduleId = $toggle->moduleId;
            $navItems = [];

            if (null !== $moduleId && isset($catalogByModuleId[$moduleId])) {
                foreach ($catalogByModuleId[$moduleId] as $section) {
                    foreach ($section->items as $item) {
                        $navItems[] = ['labelKey' => $item->labelKey, 'icon' => $item->icon];
                    }
                }
            }

            $subModules = [];
            foreach ($clientToggles as $child) {
                if ($child->parentKey !== $toggle->key) {
                    continue;
                }

                $subModules[] = $this->toggleToArray($child);
            }

            $parameters[] = [...$this->toggleToArray($toggle), 'navItems' => $navItems, 'subModules' => $subModules];
        }

        return ['parameters' => $parameters];
    }

    /** @return array<string, mixed> */
    private function toggleToArray(ModuleToggle $toggle): array
    {
        return [
            'key' => $toggle->key,
            'label' => $this->translator->trans($toggle->labelKey),
            'description' => $this->translator->trans($toggle->descriptionKey),
            'value' => $this->settingRepository->get($toggle->key, '1'),
            'requires' => $toggle->parentKey,
            'type' => ModuleToggleTypeEnum::fromKey($toggle->key)->value,
        ];
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

<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Twig;

use Aurora\Core\Frontend\Service\Registry;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Twig\Attribute\AsTwigFunction;

/**
 * Twig accessors for the frontend registry. Currently exposes whether
 * at least one front is enabled, so the sidemenu can hide the "Voir le
 * site" link when every front has been masked from the dev panel.
 */
final readonly class FrontendExtension
{
    public function __construct(
        private Registry $registry,
        private SettingRepository $settingRepository,
    ) {}

    #[AsTwigFunction(name: 'has_enabled_fronts')]
    public function hasEnabledFronts(): bool
    {
        foreach ($this->registry->all() as $front) {
            $settingKey = $front->getModuleSettingKey();
            if (null === $settingKey || $this->settingRepository->getBoolean($settingKey, true)) {
                return true;
            }
        }

        return false;
    }
}

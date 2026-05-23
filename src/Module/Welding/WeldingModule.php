<?php

declare(strict_types=1);

namespace Aurora\Module\Welding;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class WeldingModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private WeldingContext $weldingContext) {}

    public function getId(): string
    {
        return 'welding';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('welding.use'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->weldingContext->isBackendEnabled()) {
            return [];
        }

        return [
            new NavSection('welding', [
                new NavItem('backend_welding', 'backend.nav.welding', 'flame',
                    requiredPrivilege: 'welding.use',
                    descriptionKey: 'backend.nav.welding_description'),
            ], priority: 52),
        ];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('welding', [
                new NavItem('backend_welding', 'backend.nav.welding', 'flame',
                    requiredPrivilege: 'welding.use',
                    descriptionKey: 'backend.nav.welding_description'),
            ], priority: 52),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::WeldingBackend->toToggle(),
        ];
    }
}

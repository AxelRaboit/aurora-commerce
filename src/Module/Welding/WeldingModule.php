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
            new NavPermission('welding.workflow_templates.view'),
            new NavPermission('welding.workflow_templates.create'),
            new NavPermission('welding.workflow_templates.edit'),
            new NavPermission('welding.workflow_templates.delete'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->weldingContext->isBackendEnabled()) {
            return [];
        }

        return [
            new NavSection('welding', [
                new NavItem('backend_welding_workflow_templates', 'backend.nav.welding_workflow_templates', 'scroll-text',
                    requiredPrivilege: 'welding.workflow_templates.view',
                    descriptionKey: 'backend.nav.welding_workflow_templates_description'),
            ], priority: 52),
        ];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('welding', [
                new NavItem('backend_welding_workflow_templates', 'backend.nav.welding_workflow_templates', 'scroll-text',
                    requiredPrivilege: 'welding.workflow_templates.view',
                    descriptionKey: 'backend.nav.welding_workflow_templates_description'),
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

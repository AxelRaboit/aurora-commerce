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
            new NavPermission('welding.workflows.view'),
            new NavPermission('welding.workflows.start'),
            new NavPermission('welding.workflows.fill'),
            new NavPermission('welding.workflows.validate'),
            new NavPermission('welding.workflows.archive'),
            new NavPermission('welding.pdf_templates.view'),
            new NavPermission('welding.pdf_templates.create'),
            new NavPermission('welding.pdf_templates.edit'),
            new NavPermission('welding.pdf_templates.delete'),
            new NavPermission('welding.pdf_documents.view'),
            new NavPermission('welding.pdf_documents.generate'),
            new NavPermission('welding.pdf_documents.delete'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->weldingContext->isBackendEnabled()) {
            return [];
        }

        return [
            new NavSection('welding', $this->navItems(), priority: 52),
        ];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('welding', $this->navItems(), priority: 52),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::WeldingBackend->toToggle(),
            ModuleParameterEnum::WeldingPdfTemplates->toToggle(),
            ModuleParameterEnum::WeldingPdfDocuments->toToggle(),
        ];
    }

    /** @return list<NavItem> */
    private function navItems(): array
    {
        // PDF templates + generated documents are managed inline from the
        // workflow template editor and from the runner respectively — no
        // standalone nav items. Their routes/controllers still exist so the
        // existing pages remain reachable via direct URL if needed.
        return [
            new NavItem(
                'backend_welding_workflows',
                'backend.nav.welding_workflows',
                'clipboard-check',
                requiredPrivilege: 'welding.workflows.view',
                descriptionKey: 'backend.nav.welding_workflows_description'
            ),
            new NavItem(
                'backend_welding_workflow_templates',
                'backend.nav.welding_workflow_templates',
                'scroll-text',
                requiredPrivilege: 'welding.workflow_templates.view',
                descriptionKey: 'backend.nav.welding_workflow_templates_description'
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\PdfForm\PdfFormContext;

final readonly class PdfFormModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private PdfFormContext $pdfFormContext) {}

    public function getId(): string
    {
        return 'pdfform';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('pdfform.templates.view'),
            new NavPermission('pdfform.templates.create'),
            new NavPermission('pdfform.templates.edit'),
            new NavPermission('pdfform.templates.delete'),
            new NavPermission('pdfform.documents.view'),
            new NavPermission('pdfform.documents.generate'),
            new NavPermission('pdfform.documents.delete'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->pdfFormContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->pdfFormContext->isTemplatesEnabled()) {
            $items[] = new NavItem('backend_pdfform_templates', 'backend.nav.pdfform_templates', 'file-text', requiredPrivilege: 'pdfform.templates.view', descriptionKey: 'backend.nav.pdfform_templates_description');
        }

        if ($this->pdfFormContext->isDocumentsEnabled()) {
            $items[] = new NavItem('backend_pdfform_documents', 'backend.nav.pdfform_documents', 'file-output', requiredPrivilege: 'pdfform.documents.view', descriptionKey: 'backend.nav.pdfform_documents_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('pdfform', $items, priority: 34)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('pdfform', [
                new NavItem('backend_pdfform_templates', 'backend.nav.pdfform_templates', 'file-text', requiredPrivilege: 'pdfform.templates.view', descriptionKey: 'backend.nav.pdfform_templates_description'),
                new NavItem('backend_pdfform_documents', 'backend.nav.pdfform_documents', 'file-output', requiredPrivilege: 'pdfform.documents.view', descriptionKey: 'backend.nav.pdfform_documents_description'),
            ], priority: 34),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::PdfFormBackend->toToggle(),
            ModuleParameterEnum::PdfFormTemplates->toToggle(),
            ModuleParameterEnum::PdfFormDocuments->toToggle(),
        ];
    }
}

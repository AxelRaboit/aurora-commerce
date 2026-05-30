<?php

declare(strict_types=1);

namespace Aurora\Module\Billing;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Billing\Setting\BillingModuleParameterEnum;

final readonly class BillingModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private BillingContext $billingContext) {}

    public function getId(): string
    {
        return 'billing';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('billing.invoices.view'),
            new NavPermission('billing.invoices.create'),
            new NavPermission('billing.invoices.edit'),
            new NavPermission('billing.invoices.delete'),
            new NavPermission('billing.tiers.view'),
            new NavPermission('billing.tiers.edit'),
            new NavPermission('billing.tiers.delete'),
            new NavPermission('billing.ocr.import'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->billingContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->billingContext->isTiersEnabled()) {
            $items[] = new NavItem('backend_billing_tiers', 'backend.nav.tiers', 'users', requiredPrivilege: 'billing.tiers.view', descriptionKey: 'backend.nav.tiers_description');
        }

        if ($this->billingContext->isInvoicesEnabled()) {
            $items[] = new NavItem(
                'backend_billing_invoices',
                'backend.nav.invoices',
                'receipt',
                requiredPrivilege: 'billing.invoices.view',
                children: [
                    new NavItem(
                        'backend_billing_ocr_import',
                        'backend.nav.ocr_import',
                        'scan-line',
                        requiredPrivilege: 'billing.ocr.import',
                        activeRoutePrefix: 'backend_billing_ocr_',
                        descriptionKey: 'backend.nav.ocr_import_description',
                    ),
                ],
                descriptionKey: 'backend.nav.invoices_description',
            );
        }

        if ($this->billingContext->isComplianceEnabled()) {
            $items[] = new NavItem('backend_billing_compliance', 'backend.billing.compliance.title', 'shield-check', requiredPrivilege: 'billing.invoices.view', descriptionKey: 'backend.billing.compliance.description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('billing', $items, priority: 55)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('billing', [
                new NavItem(
                    'backend_billing_invoices',
                    'backend.nav.invoices',
                    'receipt',
                    requiredPrivilege: 'billing.invoices.view',
                    children: [
                        new NavItem(
                            'backend_billing_ocr_import',
                            'backend.nav.ocr_import',
                            'scan-line',
                            activeRoutePrefix: 'backend_billing_ocr_',
                            descriptionKey: 'backend.nav.ocr_import_description',
                        ),
                    ],
                    descriptionKey: 'backend.nav.invoices_description',
                ),
                new NavItem('backend_billing_tiers', 'backend.nav.tiers', 'users', requiredPrivilege: 'billing.tiers.view', descriptionKey: 'backend.nav.tiers_description'),
                new NavItem('backend_billing_compliance', 'backend.billing.compliance.title', 'shield-check', requiredPrivilege: 'billing.invoices.view', descriptionKey: 'backend.billing.compliance.description'),
            ], priority: 55),
        ];
    }

    public function getToggles(): array
    {
        return [
            BillingModuleParameterEnum::Backend->toToggle(),
            BillingModuleParameterEnum::Tiers->toToggle(),
            BillingModuleParameterEnum::Invoices->toToggle(),
            BillingModuleParameterEnum::Compliance->toToggle(),
        ];
    }
}

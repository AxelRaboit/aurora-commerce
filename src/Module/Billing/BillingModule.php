<?php

declare(strict_types=1);

namespace Aurora\Module\Billing;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\ModuleToggleProviderInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Billing\Service\BillingContext;

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
            new NavPermission('billing.tiers.manage'),
            new NavPermission('billing.ocr.import'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->billingContext->isAdminEnabled()) {
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
            ModuleParameterEnum::BillingEnabled->toToggle(),
            ModuleParameterEnum::BillingTiersEnabled->toToggle(),
            ModuleParameterEnum::BillingInvoicesEnabled->toToggle(),
            ModuleParameterEnum::BillingComplianceEnabled->toToggle(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Billing;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Module\Billing\Service\BillingContext;

final readonly class BillingModule implements ModuleInterface
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

        return [
            new NavSection('billing', [
                new NavItem(
                    'backend_billing_invoices',
                    'admin.nav.invoices',
                    'receipt',
                    children: [
                        new NavItem(
                            'backend_billing_ocr_import',
                            'admin.nav.ocr_import',
                            'scan-line',
                            activeRoutePrefix: 'backend_billing_ocr_',
                        ),
                    ],
                ),
                new NavItem('backend_billing_tiers', 'admin.nav.tiers', 'users'),
                new NavItem('backend_billing_compliance', 'admin.billing.compliance.title', 'shield-check'),
            ], priority: 55),
        ];
    }
}

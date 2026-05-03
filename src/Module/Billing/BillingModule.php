<?php

declare(strict_types=1);

namespace Aurora\Module\Billing;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\User\Enum\UserRoleEnum;
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
            new NavPermission('billing.invoices.view', UserRoleEnum::Editor->value),
            new NavPermission('billing.invoices.create', UserRoleEnum::Editor->value),
            new NavPermission('billing.invoices.edit', UserRoleEnum::Editor->value),
            new NavPermission('billing.invoices.delete', UserRoleEnum::Admin->value),
            new NavPermission('billing.tiers.view', UserRoleEnum::Editor->value),
            new NavPermission('billing.tiers.manage', UserRoleEnum::Editor->value),
            new NavPermission('billing.ocr.import', UserRoleEnum::Editor->value),
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
                    'billing_invoices',
                    'admin.nav.invoices',
                    'receipt',
                    UserRoleEnum::Editor->value,
                    children: [
                        new NavItem(
                            'billing_ocr_import',
                            'admin.nav.ocr_import',
                            'scan-line',
                            UserRoleEnum::Editor->value,
                            activeRoutePrefix: 'billing_ocr_',
                        ),
                    ],
                ),
                new NavItem('billing_tiers', 'admin.nav.tiers', 'users', UserRoleEnum::Editor->value),
                new NavItem('billing_compliance', 'admin.billing.compliance.title', 'shield-check', UserRoleEnum::Admin->value),
            ], priority: 55),
        ];
    }
}

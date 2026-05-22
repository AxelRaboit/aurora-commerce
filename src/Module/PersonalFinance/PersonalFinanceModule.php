<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class PersonalFinanceModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private PersonalFinanceContext $personalFinanceContext) {}

    public function getId(): string
    {
        return 'personal_finance';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('personal_finance.wallets.use'),
            new NavPermission('personal_finance.categories.use'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->personalFinanceContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->personalFinanceContext->isWalletsEnabled()) {
            $items[] = new NavItem(
                'backend_personal_finance_wallets',
                'backend.nav.personal_finance_wallets',
                'wallet',
                requiredPrivilege: 'personal_finance.wallets.use',
                descriptionKey: 'backend.nav.personal_finance_wallets_description',
            );
        }

        if ($this->personalFinanceContext->isCategoriesEnabled()) {
            $items[] = new NavItem(
                'backend_personal_finance_categories',
                'backend.nav.personal_finance_categories',
                'tags',
                requiredPrivilege: 'personal_finance.categories.use',
                descriptionKey: 'backend.nav.personal_finance_categories_description',
            );
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('personal_finance', $items, priority: 25)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('personal_finance', [
                new NavItem(
                    'backend_personal_finance_wallets',
                    'backend.nav.personal_finance_wallets',
                    'wallet',
                    requiredPrivilege: 'personal_finance.wallets.use',
                    descriptionKey: 'backend.nav.personal_finance_wallets_description',
                ),
                new NavItem(
                    'backend_personal_finance_categories',
                    'backend.nav.personal_finance_categories',
                    'tags',
                    requiredPrivilege: 'personal_finance.categories.use',
                    descriptionKey: 'backend.nav.personal_finance_categories_description',
                ),
            ], priority: 25),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::PersonalFinanceBackend->toToggle(),
            ModuleParameterEnum::PersonalFinanceWallets->toToggle(),
            ModuleParameterEnum::PersonalFinanceCategories->toToggle(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\PersonalFinance\Setting\PersonalFinanceModuleParameterEnum;

final readonly class PersonalFinanceContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Backend->value);
    }

    public function isWalletsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Wallets->value);
    }

    public function isCategoriesEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Categories->value);
    }

    public function isTransactionsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Transactions->value);
    }

    public function isBudgetsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Budgets->value);
    }

    public function isGoalsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Goals->value);
    }

    public function isRecurringEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Recurring->value);
    }

    public function isCategorizationEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Categorization->value);
    }

    public function isOverviewEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Overview->value);
    }

    public function isStatisticsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Statistics->value);
    }

    public function isBudgetPresetsEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::BudgetPresets->value);
    }

    public function isImportEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(PersonalFinanceModuleParameterEnum::Import->value);
    }
}

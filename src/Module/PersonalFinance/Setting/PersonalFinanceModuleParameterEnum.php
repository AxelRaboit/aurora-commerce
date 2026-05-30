<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;
use Aurora\Module\PersonalFinance\PersonalFinanceModule;

/**
 * PersonalFinance module's own access toggles (one row each in core_settings,
 * group `modules`). Self-contained so the PersonalFinance module declares its
 * toggles without the central `ModuleParameterEnum` — that core enum no longer
 * knows about PersonalFinance (monorepo-split: each module owns its toggle
 * definitions).
 *
 * Exposed to the toggle machinery via
 * {@see PersonalFinanceModule} (getToggles) and
 * to the settings sync via {@see PersonalFinanceModuleParameterProvider}.
 * Stored keys are unchanged from the legacy central enum (no migration / no
 * settings wipe).
 */
enum PersonalFinanceModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_personal_finance_backend';
    case Wallets = 'modules_personal_finance_wallets';
    case Categories = 'modules_personal_finance_categories';
    case Transactions = 'modules_personal_finance_transactions';
    case Budgets = 'modules_personal_finance_budgets';
    case Goals = 'modules_personal_finance_goals';
    case Recurring = 'modules_personal_finance_recurring';
    case Categorization = 'modules_personal_finance_categorization';
    case Overview = 'modules_personal_finance_overview';
    case Statistics = 'modules_personal_finance_statistics';
    case BudgetPresets = 'modules_personal_finance_budget_presets';
    case Import = 'modules_personal_finance_import';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.personal_finance_backend',
            self::Wallets => 'backend.nav.personal_finance_wallets',
            self::Categories => 'backend.nav.personal_finance_categories',
            self::Transactions => 'backend.nav.personal_finance_transactions',
            self::Budgets => 'backend.nav.personal_finance_budgets',
            self::Goals => 'backend.nav.personal_finance_goals',
            self::Recurring => 'backend.nav.personal_finance_recurring',
            self::Categorization => 'backend.nav.personal_finance_categorization',
            self::Overview => 'backend.nav.personal_finance_overview',
            self::Statistics => 'backend.nav.personal_finance_statistics',
            self::BudgetPresets => 'backend.nav.personal_finance_budget_presets',
            self::Import => 'backend.nav.personal_finance_import',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.personal_finance_backend_description',
            self::Wallets => 'backend.nav.personal_finance_wallets_description',
            self::Categories => 'backend.nav.personal_finance_categories_description',
            self::Transactions => 'backend.nav.personal_finance_transactions_description',
            self::Budgets => 'backend.nav.personal_finance_budgets_description',
            self::Goals => 'backend.nav.personal_finance_goals_description',
            self::Recurring => 'backend.nav.personal_finance_recurring_description',
            self::Categorization => 'backend.nav.personal_finance_categorization_description',
            self::Overview => 'backend.nav.personal_finance_overview_description',
            self::Statistics => 'backend.nav.personal_finance_statistics_description',
            self::BudgetPresets => 'backend.nav.personal_finance_budget_presets_description',
            self::Import => 'backend.nav.personal_finance_import_description',
        };
    }

    public function getDefaultValue(): string
    {
        return '1';
    }

    public function getType(): string
    {
        return 'bool';
    }

    public function getGroup(): string
    {
        return self::GROUP;
    }

    public function getPlaceholder(): ?string
    {
        return null;
    }

    /** Module identifier for the top-level toggle, null for sub-toggles. */
    private function getModuleId(): ?string
    {
        return self::Backend === $this ? 'personal_finance' : null;
    }

    /** Structural parent for dashboard grouping, null for the top-level. */
    private function getParentCase(): ?self
    {
        return match ($this) {
            self::Wallets, self::Categories, self::Transactions, self::Budgets, self::Goals, self::Recurring, self::Categorization, self::Overview, self::Statistics, self::BudgetPresets, self::Import => self::Backend,
            default => null,
        };
    }

    /** Cascade dependency (parent that must be ON), null for the top-level. */
    private function getCascadeRequires(): ?string
    {
        return match ($this) {
            self::Wallets => self::Backend->value,
            self::Categories => self::Wallets->value,
            self::Transactions => self::Wallets->value,
            self::Budgets => self::Transactions->value,
            self::Goals => self::Transactions->value,
            self::Recurring => self::Transactions->value,
            self::Categorization => self::Categories->value,
            self::Overview => self::Wallets->value,
            self::Statistics => self::Transactions->value,
            self::BudgetPresets => self::Budgets->value,
            self::Import => self::Transactions->value,
            default => null,
        };
    }

    public function toToggle(): ModuleToggle
    {
        return new ModuleToggle(
            key: $this->value,
            labelKey: $this->getLabel(),
            descriptionKey: $this->getDescription(),
            parentKey: $this->getCascadeRequires(),
            moduleId: $this->getModuleId(),
            displayParentKey: $this->getParentCase()?->value,
        );
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPreset;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItemInterface;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRule;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategory;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoal;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWallet;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitation;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMember;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;

/** Self-contained bundle for the PersonalFinance module. @see AbstractAuroraModuleBundle */
final class AuroraPersonalFinanceBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'PersonalFinance';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            PersonalFinanceWalletInterface::class => PersonalFinanceWallet::class,
            PersonalFinanceWalletMemberInterface::class => PersonalFinanceWalletMember::class,
            PersonalFinanceWalletInvitationInterface::class => PersonalFinanceWalletInvitation::class,
            PersonalFinanceCategoryInterface::class => PersonalFinanceCategory::class,
            PersonalFinanceTransactionInterface::class => PersonalFinanceTransaction::class,
            PersonalFinanceBudgetInterface::class => PersonalFinanceBudget::class,
            PersonalFinanceBudgetItemInterface::class => PersonalFinanceBudgetItem::class,
            PersonalFinanceBudgetPresetInterface::class => PersonalFinanceBudgetPreset::class,
            PersonalFinanceBudgetPresetItemInterface::class => PersonalFinanceBudgetPresetItem::class,
            PersonalFinanceGoalInterface::class => PersonalFinanceGoal::class,
            PersonalFinanceRecurringTransactionInterface::class => PersonalFinanceRecurringTransaction::class,
            PersonalFinanceScheduledTransactionInterface::class => PersonalFinanceScheduledTransaction::class,
            PersonalFinanceCategorizationRuleInterface::class => PersonalFinanceCategorizationRule::class,
        ];
    }
}

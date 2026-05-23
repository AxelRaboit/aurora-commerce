<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Manager;

use Aurora\Module\PersonalFinance\Categorization\Dto\PersonalFinanceCategorizationRuleInputInterface;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceCategorizationRuleManagerInterface
{
    /**
     * Creates a fresh rule. Only called by the Learn service — users
     * never create rules manually; they're inferred from saved
     * transactions.
     */
    public function create(CoreUserInterface $user, string $pattern, PersonalFinanceCategoryInterface $category): PersonalFinanceCategorizationRuleInterface;

    /**
     * Reassigns a rule's category from a resolved category instance
     * (used internally by the Learn service when a user re-tags an
     * existing pattern by saving a transaction).
     */
    public function updateCategory(PersonalFinanceCategorizationRuleInterface $rule, PersonalFinanceCategoryInterface $category): void;

    /**
     * DTO-driven update — used by the admin UI when the user fixes a
     * mis-learned pattern. The controller resolves + validates the
     * category, then hands both the DTO and the entity to the manager
     * so the applyInput hook can be overridden uniformly.
     */
    public function update(PersonalFinanceCategorizationRuleInterface $rule, PersonalFinanceCategorizationRuleInputInterface $input, PersonalFinanceCategoryInterface $category): void;

    public function delete(PersonalFinanceCategorizationRuleInterface $rule): void;
}

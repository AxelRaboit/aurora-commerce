<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Manager;

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
     * Reassigns a rule's category (for instance when the user fixes a
     * mis-learned pattern via the admin page).
     */
    public function updateCategory(PersonalFinanceCategorizationRuleInterface $rule, PersonalFinanceCategoryInterface $category): void;

    public function delete(PersonalFinanceCategorizationRuleInterface $rule): void;
}

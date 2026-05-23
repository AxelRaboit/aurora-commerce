<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Service;

use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceCategorizationLearnServiceInterface
{
    /**
     * Updates the categorization knowledge with the (description →
     * category) signal from a saved transaction. No-op when description
     * is blank or category is a system category (transfer/balance
     * adjustment) — those shouldn't pollute user-facing suggestions.
     */
    public function learn(CoreUserInterface $user, ?string $description, PersonalFinanceCategoryInterface $category): void;
}

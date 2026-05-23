<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Service;

use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceCategorizationSuggestServiceInterface
{
    /**
     * Returns the suggested category for the given description, or
     * null when no rule applies. Exact-match on the normalized pattern
     * — no fuzzy matching in V1.
     */
    public function suggest(CoreUserInterface $user, ?string $description): ?PersonalFinanceCategoryInterface;

    /**
     * Bulk variant — single SQL pass for an import preview.
     *
     * @param list<string> $descriptions
     *
     * @return array<string, PersonalFinanceCategoryInterface> Keyed by the input description (not the normalized pattern)
     */
    public function suggestBulk(CoreUserInterface $user, array $descriptions): array;
}

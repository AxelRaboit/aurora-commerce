<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Import\Dto;

/**
 * Returned by `PersonalFinanceImportService::process` — drives the
 * "import done" toast + summary card.
 */
class PersonalFinanceImportReport
{
    /**
     * @param list<string> $categoriesCreated
     * @param list<string> $skippedRows       human-readable line per skipped row (rowNumber + reason)
     */
    public function __construct(
        public readonly int $createdCount,
        public readonly int $skippedCount,
        public readonly array $categoriesCreated,
        public readonly array $skippedRows,
    ) {}
}

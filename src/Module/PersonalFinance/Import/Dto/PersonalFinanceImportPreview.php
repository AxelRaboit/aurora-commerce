<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Import\Dto;

/**
 * Output of `PersonalFinanceImportService::parseUpload` — held in
 * memory, never persisted. Carries every parsed row (valid + invalid),
 * the diff with existing categories so the UI can warn about cats that
 * will be created on commit, and any fatal sheet-level errors (missing
 * header, unsupported file, > maxRows).
 */
class PersonalFinanceImportPreview
{
    /**
     * @param list<PersonalFinanceImportRow> $rows
     * @param list<string>                   $newCategoryNames category names found in the sheet that don't exist on the wallet yet (will be auto-created on process)
     * @param list<string>                   $fatalErrors      sheet-level errors that block the whole import
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $newCategoryNames,
        public readonly array $fatalErrors,
    ) {}

    public function validRowCount(): int
    {
        return count(array_filter($this->rows, static fn (PersonalFinanceImportRow $r): bool => $r->isValid()));
    }

    public function invalidRowCount(): int
    {
        return count($this->rows) - $this->validRowCount();
    }

    public function isBlocking(): bool
    {
        return [] !== $this->fatalErrors;
    }
}

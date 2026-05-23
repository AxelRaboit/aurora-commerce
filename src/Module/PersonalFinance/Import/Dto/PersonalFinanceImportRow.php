<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Import\Dto;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;

/**
 * Parsed row from an uploaded Excel sheet. Holds everything needed to
 * eventually build a `PersonalFinanceTransactionInput`, plus the raw
 * inputs + a list of per-row validation errors so the preview UI can
 * surface them in red. `valid` is the AND of "no errors AND required
 * cells filled" — only valid rows get processed when the user confirms.
 *
 * Non-`final` so a client can subclass to attach extra fields read
 * from non-standard sheets (e.g. account / counterparty columns).
 */
class PersonalFinanceImportRow
{
    /**
     * @param list<string> $tags
     * @param list<string> $errors
     */
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?DateTimeImmutable $date,
        public readonly ?PersonalFinanceTransactionTypeEnum $type,
        public readonly ?string $amount,
        public readonly ?string $categoryName,
        public readonly ?string $description,
        public readonly array $tags,
        public readonly array $errors,
        public readonly array $rawValues,
    ) {}

    public function isValid(): bool
    {
        return [] === $this->errors;
    }
}

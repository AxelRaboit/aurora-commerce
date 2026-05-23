<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Split\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceSplitInputFactoryInterface::class)]
class PersonalFinanceSplitInputFactory implements PersonalFinanceSplitInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceSplitInputInterface
    {
        $typeValue = Str::trimFromArray($data, 'type');
        $type = PersonalFinanceTransactionTypeEnum::tryFrom($typeValue) ?? PersonalFinanceTransactionTypeEnum::Expense;

        $dateValue = Str::trimOrNullFromArray($data, 'date');
        $date = null;
        if (null !== $dateValue) {
            try {
                $date = new DateTimeImmutable($dateValue);
            } catch (Exception) {
                $date = null;
            }
        }

        $tags = [];
        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                $tag = mb_trim((string) $tag);
                if ('' !== $tag) {
                    $tags[] = $tag;
                }
            }
        }

        $parts = [];
        if (isset($data['parts']) && is_array($data['parts'])) {
            foreach ($data['parts'] as $part) {
                if (!is_array($part)) {
                    continue;
                }

                $parts[] = new PersonalFinanceSplitPart(
                    categoryId: isset($part['categoryId']) ? (int) $part['categoryId'] : null,
                    amount: Str::trimFromArray($part, 'amount') ?: '0.00',
                    description: Str::trimOrNullFromArray($part, 'description'),
                );
            }
        }

        return new PersonalFinanceSplitInput(
            type: $type,
            date: $date,
            description: Str::trimOrNullFromArray($data, 'description'),
            tags: $tags,
            parts: $parts,
        );
    }
}

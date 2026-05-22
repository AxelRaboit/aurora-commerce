<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceTransactionInputFactoryInterface::class)]
class PersonalFinanceTransactionInputFactory implements PersonalFinanceTransactionInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceTransactionInputInterface
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

        return new PersonalFinanceTransactionInput(
            type: $type,
            amount: Str::trimFromArray($data, 'amount') ?: '0.00',
            date: $date,
            description: Str::trimOrNullFromArray($data, 'description'),
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            tags: $tags,
        );
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Serializer;

use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceRecurringTransactionSerializerInterface::class)]
class PersonalFinanceRecurringTransactionSerializer implements PersonalFinanceRecurringTransactionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceRecurringTransactionInterface $rec): array
    {
        return [
            'id' => $rec->getId(),
            'walletId' => $rec->getWallet()->getId(),
            'walletName' => $rec->getWallet()->getName(),
            'categoryId' => $rec->getCategory()?->getId(),
            'categoryName' => $rec->getCategory()?->getName(),
            'type' => $rec->getType()->value,
            'amount' => $rec->getAmount(),
            'description' => $rec->getDescription(),
            'dayOfMonth' => $rec->getDayOfMonth(),
            'active' => $rec->isActive(),
            'lastGeneratedAt' => $rec->getLastGeneratedAt()?->format('Y-m-d'),
            'nextExpectedDate' => $this->guessNextExpectedDate($rec)?->format('Y-m-d'),
            'createdAt' => $rec->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $rec->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * Returns the next date a transaction will be generated for this
     * rule — either later this month (if not yet generated) or the
     * same day next month. Inactive rules return null.
     */
    private function guessNextExpectedDate(PersonalFinanceRecurringTransactionInterface $rec): ?DateTimeImmutable
    {
        if (!$rec->isActive()) {
            return null;
        }

        $today = new DateTimeImmutable('today');
        $thisMonthCandidate = $today->setDate((int) $today->format('Y'), (int) $today->format('n'), $rec->getDayOfMonth());

        if ($rec->getLastGeneratedAt()?->format('Y-m') === $today->format('Y-m')) {
            return $thisMonthCandidate->modify('first day of next month')->setDate(
                (int) $thisMonthCandidate->modify('first day of next month')->format('Y'),
                (int) $thisMonthCandidate->modify('first day of next month')->format('n'),
                $rec->getDayOfMonth(),
            );
        }

        return $thisMonthCandidate;
    }
}

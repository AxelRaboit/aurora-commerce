<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\View;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Module\PersonalFinance\Budget\Serializer\PersonalFinanceBudgetItemSerializerInterface;
use Aurora\Module\PersonalFinance\Budget\Serializer\PersonalFinanceBudgetSerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use DateTimeImmutable;

/**
 * Assembles the JSON payload for `GET /wallets/{id}/budget?month=YYYY-MM`.
 * Pulls items + computed actuals + balance snapshot in one round trip
 * so the Budget UI doesn't have to fan out additional calls.
 */
final readonly class PersonalFinanceBudgetViewBuilder
{
    public function __construct(
        private PersonalFinanceBudgetItemRepository $itemRepository,
        private PersonalFinanceBudgetItemSerializerInterface $itemSerializer,
        private PersonalFinanceBudgetSerializerInterface $budgetSerializer,
        private PersonalFinanceTransactionRepository $transactionRepository,
        private PersonalFinanceWalletBalanceServiceInterface $balanceService,
    ) {}

    /** @return array<string, mixed> */
    public function buildShowPayload(PersonalFinanceBudgetInterface $budget): array
    {
        $wallet = $budget->getWallet();
        $month = $budget->getMonth();
        $end = $month->modify('first day of next month');

        $actuals = $this->transactionRepository->actualsByCategoryForMonth($wallet, $month, $end);
        $items = $this->itemRepository->findByBudget($budget);

        $serializedBySection = $this->groupItemsBySection($items, $actuals);

        return [
            'success' => true,
            'budget' => $this->budgetSerializer->serialize($budget),
            'sections' => $serializedBySection,
            'balance' => $this->balanceService->snapshot($wallet, $month),
        ];
    }

    /**
     * @param list<\Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface> $items
     * @param array<int, string>                                                                   $actualsByCategoryId
     *
     * @return array<string, array<string, mixed>>
     */
    private function groupItemsBySection(array $items, array $actualsByCategoryId): array
    {
        $byBucket = [];
        foreach (PersonalFinanceBudgetSectionEnum::cases() as $section) {
            $byBucket[$section->value] = [
                'planned' => '0.00',
                'expected' => '0.00',
                'actual' => '0.00',
                'items' => [],
            ];
        }

        foreach ($items as $item) {
            $categoryId = $item->getCategory()?->getId();
            $actual = null === $categoryId ? '0.00' : ($actualsByCategoryId[$categoryId] ?? '0.00');
            $bucket = &$byBucket[$item->getSection()->value];
            $bucket['planned'] = bcadd($bucket['planned'], $item->getPlannedAmount(), 2);
            $expected = bcadd($item->getPlannedAmount(), $item->getCarriedOver(), 2);
            $bucket['expected'] = bcadd($bucket['expected'], $expected, 2);
            $bucket['actual'] = bcadd($bucket['actual'], $actual, 2);
            $bucket['items'][] = $this->itemSerializer->serialize($item, $actual);
            unset($bucket);
        }

        return $byBucket;
    }
}

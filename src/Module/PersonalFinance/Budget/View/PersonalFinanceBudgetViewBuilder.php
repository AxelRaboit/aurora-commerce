<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\View;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Module\PersonalFinance\Budget\Serializer\PersonalFinanceBudgetItemSerializerInterface;
use Aurora\Module\PersonalFinance\Budget\Serializer\PersonalFinanceBudgetSerializerInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Assembles the JSON payload for `GET /wallets/{id}/budget?month=YYYY-MM`.
 * Pulls items + computed actuals + balance snapshot in one round trip
 * so the Budget UI doesn't have to fan out additional calls.
 */
final readonly class PersonalFinanceBudgetViewBuilder
{
    public function __construct(
        private PersonalFinanceBudgetManagerInterface $budgetManager,
        private PersonalFinanceBudgetItemRepository $itemRepository,
        private PersonalFinanceBudgetItemSerializerInterface $itemSerializer,
        private PersonalFinanceBudgetSerializerInterface $budgetSerializer,
        private PersonalFinanceTransactionRepository $transactionRepository,
        private PersonalFinanceWalletBalanceServiceInterface $balanceService,
        private PersonalFinanceWalletRepository $walletRepository,
        private PersonalFinanceWalletSerializerInterface $walletSerializer,
        private PersonalFinanceCategoryRepository $categoryRepository,
        private PersonalFinanceCategorySerializerInterface $categorySerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user, ?int $walletId, DateTimeImmutable $month): array
    {
        $wallets = $this->walletRepository->findAccessibleByUser($user);
        $selectedWallet = $this->resolveWallet($wallets, $walletId);

        $categoriesByWallet = [];
        foreach ($wallets as $wallet) {
            $categoriesByWallet[(string) $wallet->getId()] = array_map(
                $this->categorySerializer->serialize(...),
                $this->categoryRepository->findUserCategoriesByWallet($wallet),
            );
        }

        $payload = ($selectedWallet instanceof PersonalFinanceWalletInterface)
            ? $this->buildShowPayload($this->budgetManager->ensureForMonth($user, $selectedWallet, $month))
            : ['success' => true, 'budget' => null, 'sections' => [], 'balance' => ['current' => '0.00', 'month' => '0.00', 'rollingStart' => '0.00']];

        return [
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'categoriesByWallet' => $categoriesByWallet,
            'selectedWalletId' => $selectedWallet?->getId(),
            'month' => $month->format('Y-m'),
            'sections' => PersonalFinanceBudgetSectionEnum::values(),
            'types' => PersonalFinanceTransactionTypeEnum::values(),
            'budgetPayload' => $payload,
            'showBudgetPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_budget_show', ['walletId' => '__walletId__']),
            'exportBudgetPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_budget_export', ['walletId' => '__walletId__']),
            'savePresetPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_budget_presets_save_from_month', ['walletId' => '__walletId__']),
            'listPresetsPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_budget_presets_list', ['walletId' => '__walletId__']),
            'applyPresetPath' => $this->urlGenerator->generate('backend_personal_finance_budget_presets_apply', ['id' => '__id__']),
            'createItemPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_budget_items_create', ['walletId' => '__walletId__']),
            'updateItemPath' => $this->urlGenerator->generate('backend_personal_finance_budget_items_update', ['id' => '__id__']),
            'deleteItemPath' => $this->urlGenerator->generate('backend_personal_finance_budget_items_delete', ['id' => '__id__']),
            'createTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_transactions_create', ['walletId' => '__walletId__']),
            'updateTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_update', ['id' => '__id__']),
            'deleteTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_delete', ['id' => '__id__']),
            'itemTransactionsPath' => $this->urlGenerator->generate('backend_personal_finance_budget_items_transactions', ['id' => '__id__']),
            'uploadAttachmentPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_attachment_upload', ['id' => '__id__']),
            'deleteAttachmentPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_attachment_delete', ['id' => '__id__']),
            'serveAttachmentPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_attachment_serve', ['id' => '__id__']),
        ];
    }

    /**
     * @param list<PersonalFinanceWalletInterface> $wallets
     */
    public function resolveWallet(array $wallets, ?int $walletId): ?PersonalFinanceWalletInterface
    {
        if (null === $walletId) {
            return $wallets[0] ?? null;
        }

        foreach ($wallets as $wallet) {
            if ($wallet->getId() === $walletId) {
                return $wallet;
            }
        }

        return $wallets[0] ?? null;
    }

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
            'rolledOver' => $this->budgetManager->lastRolloverCount(),
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

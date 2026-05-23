<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceTransactionsViewBuilder
{
    public function __construct(
        private PersonalFinanceWalletRepository $walletRepository,
        private PersonalFinanceWalletSerializerInterface $walletSerializer,
        private PersonalFinanceCategoryRepository $categoryRepository,
        private PersonalFinanceCategorySerializerInterface $categorySerializer,
        private PersonalFinanceTransactionRepository $transactionRepository,
        private PersonalFinanceTransactionSerializerInterface $transactionSerializer,
        private PersonalFinanceWalletBalanceServiceInterface $balanceService,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user, PaginationRequest $pagination, ?int $walletId): array
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

        $month = new DateTimeImmutable('first day of this month');
        $balance = $selectedWallet instanceof PersonalFinanceWalletInterface
            ? $this->balanceService->snapshot($selectedWallet, $month)
            : ['current' => '0.00', 'month' => '0.00', 'rollingStart' => '0.00'];

        return [
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'categoriesByWallet' => $categoriesByWallet,
            'selectedWalletId' => $selectedWallet?->getId(),
            'transactions' => $selectedWallet instanceof PersonalFinanceWalletInterface
                ? $this->buildListPayload($selectedWallet, $pagination)
                : ['success' => true, 'items' => [], 'page' => 1, 'totalPages' => 1, 'total' => 0],
            'balance' => $balance,
            'balanceMonth' => $month->format('Y-m'),
            'search' => $pagination->search ?? '',
            'types' => PersonalFinanceTransactionTypeEnum::values(),
            'listPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_list'),
            'exportPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_transactions_export', ['walletId' => '__walletId__']),
            'createTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_transactions_create', ['walletId' => '__walletId__']),
            'updateTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_update', ['id' => '__id__']),
            'deleteTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_delete', ['id' => '__id__']),
            'createTransferPath' => $this->urlGenerator->generate('backend_personal_finance_transfers_create'),
            'updateTransferPath' => $this->urlGenerator->generate('backend_personal_finance_transfers_update', ['transferId' => '__transferId__']),
            'deleteTransferPath' => $this->urlGenerator->generate('backend_personal_finance_transfers_delete', ['transferId' => '__transferId__']),
            'showTransferPath' => $this->urlGenerator->generate('backend_personal_finance_transfers_show', ['transferId' => '__transferId__']),
            'createSplitPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_splits_create', ['walletId' => '__walletId__']),
            'deleteSplitPath' => $this->urlGenerator->generate('backend_personal_finance_splits_delete', ['splitId' => '__splitId__']),
            'uploadAttachmentPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_attachment_upload', ['id' => '__id__']),
            'deleteAttachmentPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_attachment_delete', ['id' => '__id__']),
            'serveAttachmentPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_attachment_serve', ['id' => '__id__']),
            'walletBalancePath' => $this->urlGenerator->generate('backend_personal_finance_wallets_balance', ['walletId' => '__walletId__']),
            'walletBalanceAdjustPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_balance_adjust', ['walletId' => '__walletId__']),
        ];
    }

    /** @return array<string, mixed> */
    public function buildListPayload(PersonalFinanceWalletInterface $wallet, PaginationRequest $pagination, ?string $tag = null): array
    {
        $result = $this->transactionRepository->findPaginatedByWallet(
            $wallet,
            $pagination->page,
            search: $pagination->search,
            tag: $tag,
        );

        return [
            'success' => true,
            'items' => array_map($this->transactionSerializer->serialize(...), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
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
}

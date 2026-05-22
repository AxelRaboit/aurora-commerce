<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\View;

use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
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
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        $wallets = $this->walletRepository->findAccessibleByUser($user);

        $categoriesByWallet = [];
        $transactionsByWallet = [];
        foreach ($wallets as $wallet) {
            $key = (string) $wallet->getId();
            $categoriesByWallet[$key] = array_map(
                $this->categorySerializer->serialize(...),
                $this->categoryRepository->findUserCategoriesByWallet($wallet),
            );
            $transactionsByWallet[$key] = array_map(
                $this->transactionSerializer->serialize(...),
                $this->transactionRepository->findByWallet($wallet),
            );
        }

        return [
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'categoriesByWallet' => $categoriesByWallet,
            'transactionsByWallet' => $transactionsByWallet,
            'types' => PersonalFinanceTransactionTypeEnum::values(),
            'createTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_transactions_create', ['walletId' => '__walletId__']),
            'updateTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_update', ['id' => '__id__']),
            'deleteTransactionPath' => $this->urlGenerator->generate('backend_personal_finance_transactions_delete', ['id' => '__id__']),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceCategoriesViewBuilder
{
    public function __construct(
        private PersonalFinanceWalletRepository $walletRepository,
        private PersonalFinanceWalletSerializerInterface $walletSerializer,
        private PersonalFinanceCategoryRepository $categoryRepository,
        private PersonalFinanceCategorySerializerInterface $categorySerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user, PaginationRequest $pagination, ?int $walletId): array
    {
        $wallets = $this->walletRepository->findAccessibleByUser($user);
        $selectedWallet = $this->resolveWallet($wallets, $walletId);

        return [
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'selectedWalletId' => $selectedWallet?->getId(),
            'categories' => $selectedWallet instanceof PersonalFinanceWalletInterface
                ? $this->buildListPayload($selectedWallet, $pagination)
                : ['success' => true, 'items' => [], 'page' => 1, 'totalPages' => 1, 'total' => 0],
            'search' => $pagination->search ?? '',
            'listPath' => $this->urlGenerator->generate('backend_personal_finance_categories_list'),
            'createCategoryPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_categories_create', ['walletId' => '__walletId__']),
            'updateCategoryPath' => $this->urlGenerator->generate('backend_personal_finance_categories_update', ['id' => '__id__']),
            'deleteCategoryPath' => $this->urlGenerator->generate('backend_personal_finance_categories_delete', ['id' => '__id__']),
        ];
    }

    /** @return array<string, mixed> */
    public function buildListPayload(PersonalFinanceWalletInterface $wallet, PaginationRequest $pagination): array
    {
        $result = $this->categoryRepository->findPaginatedUserCategoriesByWallet(
            $wallet,
            $pagination->page,
            search: $pagination->search,
        );

        return [
            'success' => true,
            'items' => array_map($this->categorySerializer->serialize(...), $result['items']),
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

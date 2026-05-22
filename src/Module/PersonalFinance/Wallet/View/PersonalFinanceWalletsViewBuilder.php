<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletModeEnum;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceWalletsViewBuilder
{
    public function __construct(
        private PersonalFinanceWalletRepository $personalFinanceWalletRepository,
        private PersonalFinanceWalletSerializerInterface $personalFinanceWalletSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user, PaginationRequest $pagination): array
    {
        return [
            'wallets' => $this->buildListPayload($user, $pagination),
            'search' => $pagination->search ?? '',
            'modes' => PersonalFinanceWalletModeEnum::values(),
            'listPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_list'),
            'createWalletPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_create'),
            'updateWalletPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_update', ['id' => '__id__']),
            'deleteWalletPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_delete', ['id' => '__id__']),
        ];
    }

    /** @return array<string, mixed> */
    public function buildListPayload(CoreUserInterface $user, PaginationRequest $pagination): array
    {
        $result = $this->personalFinanceWalletRepository->findPaginatedAccessibleByUser(
            $user,
            $pagination->page,
            search: $pagination->search,
        );

        return [
            'success' => true,
            'items' => array_map($this->personalFinanceWalletSerializer->serialize(...), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ];
    }
}

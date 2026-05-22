<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\View;

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
    public function indexView(CoreUserInterface $user): array
    {
        $wallets = $this->personalFinanceWalletRepository->findAccessibleByUser($user);

        return [
            'wallets' => array_map($this->personalFinanceWalletSerializer->serialize(...), $wallets),
            'modes' => PersonalFinanceWalletModeEnum::values(),
            'createWalletPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_create'),
            'updateWalletPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_update', ['id' => '__id__']),
            'deleteWalletPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_delete', ['id' => '__id__']),
        ];
    }
}

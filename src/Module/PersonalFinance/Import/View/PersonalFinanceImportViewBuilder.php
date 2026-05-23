<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Import\View;

use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceImportViewBuilder
{
    public function __construct(
        private PersonalFinanceWalletRepository $walletRepository,
        private PersonalFinanceWalletSerializerInterface $walletSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        $wallets = $this->walletRepository->findAccessibleByUser($user);

        return [
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'previewPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_import_preview', ['walletId' => '__walletId__']),
            'processPath' => $this->urlGenerator->generate('backend_personal_finance_wallets_import_process', ['walletId' => '__walletId__']),
            'templatePath' => $this->urlGenerator->generate('backend_personal_finance_import_template'),
        ];
    }
}

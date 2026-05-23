<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\View;

use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Goal\Repository\PersonalFinanceGoalRepository;
use Aurora\Module\PersonalFinance\Goal\Serializer\PersonalFinanceGoalSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceGoalsViewBuilder
{
    public function __construct(
        private PersonalFinanceGoalRepository $goalRepository,
        private PersonalFinanceGoalSerializerInterface $goalSerializer,
        private PersonalFinanceWalletRepository $walletRepository,
        private PersonalFinanceWalletSerializerInterface $walletSerializer,
        private PersonalFinanceCategoryRepository $categoryRepository,
        private PersonalFinanceCategorySerializerInterface $categorySerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        $wallets = $this->walletRepository->findAccessibleByUser($user);
        $categoriesByWallet = [];
        foreach ($wallets as $wallet) {
            $categoriesByWallet[(string) $wallet->getId()] = array_map(
                $this->categorySerializer->serialize(...),
                $this->categoryRepository->findUserCategoriesByWallet($wallet),
            );
        }

        $goals = array_map(
            $this->goalSerializer->serialize(...),
            $this->goalRepository->findOwnedByUser($user),
        );

        return [
            'goals' => $goals,
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'categoriesByWallet' => $categoriesByWallet,
            'createPath' => $this->urlGenerator->generate('backend_personal_finance_goals_create'),
            'updatePath' => $this->urlGenerator->generate('backend_personal_finance_goals_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_personal_finance_goals_delete', ['id' => '__id__']),
            'depositPath' => $this->urlGenerator->generate('backend_personal_finance_goals_deposit', ['id' => '__id__']),
        ];
    }
}

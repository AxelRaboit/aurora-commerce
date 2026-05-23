<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\View;

use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceScheduledTransactionRepository;
use Aurora\Module\PersonalFinance\Recurring\Serializer\PersonalFinanceRecurringTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Recurring\Serializer\PersonalFinanceScheduledTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceRecurringViewBuilder
{
    public function __construct(
        private PersonalFinanceRecurringTransactionRepository $recurringRepository,
        private PersonalFinanceRecurringTransactionSerializerInterface $recurringSerializer,
        private PersonalFinanceScheduledTransactionRepository $scheduledRepository,
        private PersonalFinanceScheduledTransactionSerializerInterface $scheduledSerializer,
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

        return [
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'categoriesByWallet' => $categoriesByWallet,
            'types' => PersonalFinanceTransactionTypeEnum::values(),
            'recurring' => array_map($this->recurringSerializer->serialize(...), $this->recurringRepository->findOwnedByUser($user)),
            'scheduled' => array_map($this->scheduledSerializer->serialize(...), $this->scheduledRepository->findOwnedByUser($user)),
            'createRecurringPath' => $this->urlGenerator->generate('backend_personal_finance_recurring_create'),
            'updateRecurringPath' => $this->urlGenerator->generate('backend_personal_finance_recurring_update', ['id' => '__id__']),
            'toggleRecurringPath' => $this->urlGenerator->generate('backend_personal_finance_recurring_toggle', ['id' => '__id__']),
            'deleteRecurringPath' => $this->urlGenerator->generate('backend_personal_finance_recurring_delete', ['id' => '__id__']),
            'createScheduledPath' => $this->urlGenerator->generate('backend_personal_finance_scheduled_create'),
            'updateScheduledPath' => $this->urlGenerator->generate('backend_personal_finance_scheduled_update', ['id' => '__id__']),
            'materializeScheduledPath' => $this->urlGenerator->generate('backend_personal_finance_scheduled_materialize', ['id' => '__id__']),
            'deleteScheduledPath' => $this->urlGenerator->generate('backend_personal_finance_scheduled_delete', ['id' => '__id__']),
        ];
    }
}

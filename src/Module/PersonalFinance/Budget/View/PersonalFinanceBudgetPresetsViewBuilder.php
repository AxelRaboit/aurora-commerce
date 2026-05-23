<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\View;

use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetPresetApplyModeEnum;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetPresetRepository;
use Aurora\Module\PersonalFinance\Budget\Serializer\PersonalFinanceBudgetPresetSerializerInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Serializer\PersonalFinanceWalletSerializerInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceBudgetPresetsViewBuilder
{
    public function __construct(
        private PersonalFinanceBudgetPresetRepository $presetRepository,
        private PersonalFinanceBudgetPresetSerializerInterface $presetSerializer,
        private PersonalFinanceWalletRepository $walletRepository,
        private PersonalFinanceWalletSerializerInterface $walletSerializer,
        private PersonalFinanceCategoryRepository $categoryRepository,
        private PersonalFinanceCategorySerializerInterface $categorySerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user, ?int $walletId): array
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

        $presets = $selectedWallet instanceof PersonalFinanceWalletInterface
            ? array_map($this->presetSerializer->serialize(...), $this->presetRepository->findByWallet($selectedWallet))
            : [];

        return [
            'wallets' => array_map($this->walletSerializer->serialize(...), $wallets),
            'categoriesByWallet' => $categoriesByWallet,
            'selectedWalletId' => $selectedWallet?->getId(),
            'presets' => $presets,
            'sections' => PersonalFinanceBudgetSectionEnum::values(),
            'applyModes' => PersonalFinanceBudgetPresetApplyModeEnum::values(),
            'listPath' => $this->urlGenerator->generate('backend_personal_finance_budget_presets_list', ['walletId' => '__walletId__']),
            'createPath' => $this->urlGenerator->generate('backend_personal_finance_budget_presets_create', ['walletId' => '__walletId__']),
            'updatePath' => $this->urlGenerator->generate('backend_personal_finance_budget_presets_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_personal_finance_budget_presets_delete', ['id' => '__id__']),
            'applyPath' => $this->urlGenerator->generate('backend_personal_finance_budget_presets_apply', ['id' => '__id__']),
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

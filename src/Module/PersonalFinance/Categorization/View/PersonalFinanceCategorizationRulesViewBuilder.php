<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\PersonalFinance\Categorization\Repository\PersonalFinanceCategorizationRuleRepository;
use Aurora\Module\PersonalFinance\Categorization\Serializer\PersonalFinanceCategorizationRuleSerializerInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Category\Serializer\PersonalFinanceCategorySerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PersonalFinanceCategorizationRulesViewBuilder
{
    public function __construct(
        private PersonalFinanceCategorizationRuleRepository $ruleRepository,
        private PersonalFinanceCategorizationRuleSerializerInterface $ruleSerializer,
        private PersonalFinanceWalletRepository $walletRepository,
        private PersonalFinanceCategoryRepository $categoryRepository,
        private PersonalFinanceCategorySerializerInterface $categorySerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user, PaginationRequest $pagination): array
    {
        $categoriesByWallet = [];
        foreach ($this->walletRepository->findAccessibleByUser($user) as $wallet) {
            $categoriesByWallet[(string) $wallet->getId()] = array_map(
                $this->categorySerializer->serialize(...),
                $this->categoryRepository->findUserCategoriesByWallet($wallet),
            );
        }

        return [
            'categoriesByWallet' => $categoriesByWallet,
            'rules' => $this->buildListPayload($user, $pagination),
            'search' => $pagination->search ?? '',
            'listPath' => $this->urlGenerator->generate('backend_personal_finance_categorization_rules_list'),
            'updatePath' => $this->urlGenerator->generate('backend_personal_finance_categorization_rules_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_personal_finance_categorization_rules_delete', ['id' => '__id__']),
            'suggestPath' => $this->urlGenerator->generate('backend_personal_finance_categorization_rules_suggest'),
        ];
    }

    /** @return array<string, mixed> */
    public function buildListPayload(CoreUserInterface $user, PaginationRequest $pagination): array
    {
        $result = $this->ruleRepository->findPaginatedForUser($user, $pagination->page, search: $pagination->search);

        return [
            'success' => true,
            'items' => array_map($this->ruleSerializer->serialize(...), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ];
    }
}

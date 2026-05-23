<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetItemInputFactoryInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetItemManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Module\PersonalFinance\Budget\Serializer\PersonalFinanceBudgetItemSerializerInterface;
use Aurora\Module\PersonalFinance\Budget\View\PersonalFinanceBudgetViewBuilder;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.budgets.use')]
final class PersonalFinanceBudgetsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceBudgetManagerInterface $budgetManager,
        private readonly PersonalFinanceBudgetItemManagerInterface $itemManager,
        private readonly PersonalFinanceBudgetItemInputFactoryInterface $itemInputFactory,
        private readonly PersonalFinanceBudgetItemRepository $itemRepository,
        private readonly PersonalFinanceBudgetItemSerializerInterface $itemSerializer,
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PersonalFinanceBudgetViewBuilder $viewBuilder,
        private readonly PersonalFinanceTransactionRepository $transactionRepository,
        private readonly PersonalFinanceTransactionSerializerInterface $transactionSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/budgets', name: '_budgets', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $walletId = $request->query->getInt('walletId') ?: null;
        $month = $this->resolveMonth($request->query->get('month'));

        return $this->render(
            '@PersonalFinance/backend/budgets/index.html.twig',
            $this->viewBuilder->indexView($user, $walletId, $month),
        );
    }

    #[Route('/wallets/{walletId}/budget', name: '_wallets_budget_show', methods: [HttpMethodEnum::Get->value])]
    public function show(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $month = $this->resolveMonth($request->query->get('month'));
        $budget = $this->budgetManager->ensureForMonth($user, $wallet, $month);

        return $this->json($this->viewBuilder->buildShowPayload($budget));
    }

    #[Route('/wallets/{walletId}/budget/items/create', name: '_wallets_budget_items_create', methods: [HttpMethodEnum::Post->value])]
    public function createItem(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $payload = $this->decodeJson($request);
        $month = $this->resolveMonth(is_string($payload['month'] ?? null) ? (string) $payload['month'] : null);
        $budget = $this->budgetManager->ensureForMonth($user, $wallet, $month);

        $input = $this->itemInputFactory->fromArray($payload);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $item = $this->itemManager->create($budget, $input);

        return $this->jsonSuccess(['item' => $this->itemSerializer->serialize($item)]);
    }

    #[Route('/budget/items/{id}/update', name: '_budget_items_update', methods: [HttpMethodEnum::Post->value])]
    public function updateItem(int $id, Request $request): JsonResponse
    {
        $item = $this->itemRepository->find($id);
        if (!$item instanceof PersonalFinanceBudgetItemInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $item->getBudget()->getWallet());

        $input = $this->itemInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->itemManager->update($item, $input);

        return $this->jsonSuccess(['item' => $this->itemSerializer->serialize($item)]);
    }

    /**
     * Drill-down: paginated list of transactions that belong to a
     * budget item's category over the item's budgeted month. Used by
     * the Budgets page to let the user audit / edit / delete the
     * actuals that feed a given line — feeds an infinite-scroll list.
     */
    #[Route('/budget/items/{id}/transactions', name: '_budget_items_transactions', methods: [HttpMethodEnum::Get->value])]
    public function itemTransactions(int $id, PaginationRequest $pagination): JsonResponse
    {
        $item = $this->itemRepository->find($id);
        if (!$item instanceof PersonalFinanceBudgetItemInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $item->getBudget()->getWallet());

        $category = $item->getCategory();
        if (null === $category) {
            return $this->jsonSuccess(['items' => [], 'page' => 1, 'totalPages' => 1, 'total' => 0]);
        }

        $result = $this->transactionRepository->findPaginatedByCategoryAndMonth(
            $category,
            $item->getBudget()->getMonth(),
            $pagination->page,
        );

        return $this->jsonSuccess([
            'items' => array_map($this->transactionSerializer->serialize(...), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ]);
    }

    #[Route('/budget/items/{id}/delete', name: '_budget_items_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteItem(int $id): JsonResponse
    {
        $item = $this->itemRepository->find($id);
        if (!$item instanceof PersonalFinanceBudgetItemInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $item->getBudget()->getWallet());

        $this->itemManager->delete($item);

        return $this->jsonSuccess();
    }

    private function resolveMonth(?string $monthParam): DateTimeImmutable
    {
        if (null === $monthParam || '' === $monthParam) {
            return new DateTimeImmutable('first day of this month');
        }
        try {
            return new DateTimeImmutable($monthParam.'-01');
        } catch (Exception) {
            return new DateTimeImmutable('first day of this month');
        }
    }
}

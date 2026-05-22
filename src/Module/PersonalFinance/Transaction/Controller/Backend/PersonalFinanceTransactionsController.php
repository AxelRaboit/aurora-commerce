<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInputFactoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Manager\PersonalFinanceTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\View\PersonalFinanceTransactionsViewBuilder;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.transactions.use')]
final class PersonalFinanceTransactionsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceTransactionSerializerInterface $transactionSerializer,
        private readonly PersonalFinanceTransactionManagerInterface $transactionManager,
        private readonly PersonalFinanceTransactionRepository $transactionRepository,
        private readonly PersonalFinanceTransactionInputFactoryInterface $transactionInputFactory,
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly PersonalFinanceTransactionsViewBuilder $viewBuilder,
    ) {}

    #[Route('/transactions', name: '_transactions', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request, PaginationRequest $pagination): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $walletId = $request->query->getInt('walletId') ?: null;

        return $this->render(
            '@PersonalFinance/backend/transactions/index.html.twig',
            $this->viewBuilder->indexView($user, $pagination, $walletId),
        );
    }

    #[Route('/transactions/list', name: '_transactions_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request, PaginationRequest $pagination): JsonResponse
    {
        $walletId = $request->query->getInt('walletId') ?: null;
        if (null === $walletId) {
            return $this->json(['success' => true, 'items' => [], 'page' => 1, 'totalPages' => 1, 'total' => 0]);
        }

        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $wallet);

        return $this->json($this->viewBuilder->buildListPayload($wallet, $pagination));
    }

    #[Route('/wallets/{walletId}/transactions/create', name: '_wallets_transactions_create', methods: [HttpMethodEnum::Post->value])]
    public function create(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->transactionInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $transaction = $this->transactionManager->create($user, $wallet, $input);

        return $this->jsonSuccess(['transaction' => $this->transactionSerializer->serialize($transaction)]);
    }

    #[Route('/transactions/{id}/update', name: '_transactions_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction instanceof PersonalFinanceTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $transaction->getWallet());

        $input = $this->transactionInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->transactionManager->update($transaction, $input);

        return $this->jsonSuccess(['transaction' => $this->transactionSerializer->serialize($transaction)]);
    }

    #[Route('/transactions/{id}/delete', name: '_transactions_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction instanceof PersonalFinanceTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $transaction->getWallet());

        $this->transactionManager->delete($transaction);

        return $this->jsonSuccess();
    }
}

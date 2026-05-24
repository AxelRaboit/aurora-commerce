<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Dto\PersonalFinanceTransferInputFactoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Service\PersonalFinanceTransferServiceInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance', name: 'backend_personal_finance')]
#[IsGranted('personal_finance.transactions.use')]
final class PersonalFinanceTransfersController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceTransferServiceInterface $transferService,
        private readonly PersonalFinanceTransferInputFactoryInterface $transferInputFactory,
        private readonly PersonalFinanceTransactionRepository $transactionRepository,
        private readonly PersonalFinanceTransactionSerializerInterface $transactionSerializer,
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/transfers/{transferId}/show', name: '_transfers_show', methods: [HttpMethodEnum::Get->value])]
    public function show(string $transferId): JsonResponse
    {
        $transactions = $this->transactionRepository->findByTransferId($transferId);
        if (2 !== count($transactions)) {
            return $this->jsonNotFound();
        }

        $expense = null;
        $income = null;
        foreach ($transactions as $tx) {
            if ('expense' === $tx->getType()->value) {
                $expense = $tx;
            } elseif ('income' === $tx->getType()->value) {
                $income = $tx;
            }

            $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $tx->getWallet());
        }

        if (!$expense instanceof PersonalFinanceTransactionInterface || !$income instanceof PersonalFinanceTransactionInterface) {
            return $this->jsonNotFound();
        }

        return $this->jsonSuccess([
            'transfer' => [
                'transferId' => $transferId,
                'fromWalletId' => $expense->getWallet()->getId(),
                'toWalletId' => $income->getWallet()->getId(),
                'amount' => $expense->getAmount(),
                'date' => $expense->getDate()->format('Y-m-d'),
                'description' => $expense->getDescription(),
            ],
        ]);
    }

    #[Route('/transfers/create', name: '_transfers_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->transferInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $fromWallet = $this->walletRepository->find($input->getFromWalletId());
        $toWallet = $this->walletRepository->find($input->getToWalletId());
        if (!$fromWallet instanceof PersonalFinanceWalletInterface || !$toWallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $fromWallet);
        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $toWallet);

        $transferId = $this->transferService->create($user, $fromWallet, $toWallet, $input);

        return $this->jsonSuccess([
            'transferId' => $transferId,
            'transactions' => array_map(
                $this->transactionSerializer->serialize(...),
                $this->transactionRepository->findByTransferId($transferId),
            ),
        ]);
    }

    #[Route('/transfers/{transferId}/update', name: '_transfers_update', methods: [HttpMethodEnum::Post->value])]
    public function update(string $transferId, Request $request): JsonResponse
    {
        $transactions = $this->transactionRepository->findByTransferId($transferId);
        if (2 !== count($transactions)) {
            return $this->jsonNotFound();
        }

        foreach ($transactions as $transaction) {
            $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $transaction->getWallet());
        }

        $input = $this->transferInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->transferService->update($transferId, $input);

        return $this->jsonSuccess([
            'transferId' => $transferId,
            'transactions' => array_map(
                $this->transactionSerializer->serialize(...),
                $this->transactionRepository->findByTransferId($transferId),
            ),
        ]);
    }

    #[Route('/transfers/{transferId}/delete', name: '_transfers_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(string $transferId): JsonResponse
    {
        $transactions = $this->transactionRepository->findByTransferId($transferId);
        if (2 !== count($transactions)) {
            return $this->jsonNotFound();
        }

        foreach ($transactions as $transaction) {
            $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $transaction->getWallet());
        }

        $this->transferService->delete($transferId);

        return $this->jsonSuccess();
    }
}

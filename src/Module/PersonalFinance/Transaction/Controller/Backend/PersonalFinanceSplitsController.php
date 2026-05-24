<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Transaction\Split\Dto\PersonalFinanceSplitInputFactoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Split\Service\PersonalFinanceSplitServiceInterface;
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
final class PersonalFinanceSplitsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceSplitServiceInterface $splitService,
        private readonly PersonalFinanceSplitInputFactoryInterface $splitInputFactory,
        private readonly PersonalFinanceTransactionRepository $transactionRepository,
        private readonly PersonalFinanceTransactionSerializerInterface $transactionSerializer,
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/wallets/{walletId}/splits/create', name: '_wallets_splits_create', methods: [HttpMethodEnum::Post->value])]
    public function create(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->splitInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $splitId = $this->splitService->create($user, $wallet, $input);

        return $this->jsonSuccess([
            'splitId' => $splitId,
            'transactions' => array_map(
                $this->transactionSerializer->serialize(...),
                $this->transactionRepository->findBySplitId($splitId),
            ),
        ]);
    }

    #[Route('/splits/{splitId}/delete', name: '_splits_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(string $splitId): JsonResponse
    {
        $transactions = $this->transactionRepository->findBySplitId($splitId);
        if ([] === $transactions) {
            return $this->jsonNotFound();
        }

        foreach ($transactions as $transaction) {
            $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $transaction->getWallet());
        }

        $this->splitService->delete($splitId);

        return $this->jsonSuccess();
    }
}

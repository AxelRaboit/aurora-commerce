<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceBalanceAdjustmentInputFactoryInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceBalanceAdjustmentServiceInterface;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use DomainException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/wallets', name: 'backend_personal_finance_wallets')]
#[IsGranted('personal_finance.wallets.use')]
final class PersonalFinanceWalletBalanceController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceWalletRepository $walletRepository,
        private readonly PersonalFinanceWalletBalanceServiceInterface $balanceService,
        private readonly PersonalFinanceBalanceAdjustmentServiceInterface $balanceAdjustmentService,
        private readonly PersonalFinanceBalanceAdjustmentInputFactoryInterface $inputFactory,
        private readonly PersonalFinanceTransactionSerializerInterface $transactionSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{walletId}/balance', name: '_balance', methods: [HttpMethodEnum::Get->value])]
    public function balance(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $wallet);

        $month = $this->resolveMonth($request->query->get('month'));

        $snapshot = $this->balanceService->snapshot($wallet, $month);

        return $this->jsonSuccess([
            'walletId' => $wallet->getId(),
            'month' => $month->format('Y-m'),
            'balance' => $snapshot,
        ]);
    }

    #[Route('/{walletId}/balance/adjust', name: '_balance_adjust', methods: [HttpMethodEnum::Post->value])]
    public function adjust(int $walletId, Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $wallet);

        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->inputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $transaction = $this->balanceAdjustmentService->adjust($user, $wallet, $input);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['newBalance' => $domainException->getMessage()]);
        }

        return $this->jsonSuccess([
            'transaction' => $this->transactionSerializer->serialize($transaction),
            'balance' => $this->balanceService->snapshot($wallet, $input->getDate()),
        ]);
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

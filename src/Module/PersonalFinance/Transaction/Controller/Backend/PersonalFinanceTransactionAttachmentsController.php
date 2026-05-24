<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Module\PersonalFinance\Transaction\Attachment\Service\PersonalFinanceTransactionAttachmentServiceInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Serializer\PersonalFinanceTransactionSerializerInterface;
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/transactions', name: 'backend_personal_finance_transactions')]
#[IsGranted('personal_finance.transactions.use')]
final class PersonalFinanceTransactionAttachmentsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceTransactionAttachmentServiceInterface $attachmentService,
        private readonly PersonalFinanceTransactionRepository $transactionRepository,
        private readonly PersonalFinanceTransactionSerializerInterface $transactionSerializer,
    ) {}

    #[Route('/{id}/attachment/upload', name: '_attachment_upload', methods: [HttpMethodEnum::Post->value])]
    public function upload(int $id, Request $request): JsonResponse
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction instanceof PersonalFinanceTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $transaction->getWallet());

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->jsonInvalidInput(['file' => 'personal_finance.transactions.attachment.errors.missing']);
        }

        try {
            $this->attachmentService->attach($transaction, $file);
        } catch (FileException $fileException) {
            return $this->jsonInvalidInput(['file' => $fileException->getMessage()]);
        }

        return $this->jsonSuccess(['transaction' => $this->transactionSerializer->serialize($transaction)]);
    }

    #[Route('/{id}/attachment/delete', name: '_attachment_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction instanceof PersonalFinanceTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::EDIT_TRANSACTIONS, $transaction->getWallet());

        $this->attachmentService->detach($transaction);

        return $this->jsonSuccess(['transaction' => $this->transactionSerializer->serialize($transaction)]);
    }

    #[Route('/{id}/attachment', name: '_attachment_serve', methods: [HttpMethodEnum::Get->value])]
    public function serve(int $id): Response
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction instanceof PersonalFinanceTransactionInterface) {
            return $this->jsonNotFound();
        }

        $this->denyAccessUnlessGranted(PersonalFinanceWalletVoter::VIEW, $transaction->getWallet());

        if (!$transaction->hasAttachment()) {
            return $this->jsonNotFound();
        }

        return $this->attachmentService->serve($transaction);
    }
}

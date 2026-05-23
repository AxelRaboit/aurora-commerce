<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Transaction\Attachment\Service\PersonalFinanceTransactionAttachmentServiceInterface;
use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInputInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionDeletedEvent;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionSavedEvent;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceTransactionManagerInterface::class)]
class PersonalFinanceTransactionManager implements PersonalFinanceTransactionManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
        protected readonly PersonalFinanceTransactionAttachmentServiceInterface $attachmentService,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function create(CoreUserInterface $user, PersonalFinanceWalletInterface $wallet, PersonalFinanceTransactionInputInterface $input): PersonalFinanceTransactionInterface
    {
        $transaction = $this->createTransaction();
        $transaction->setUser($user);
        $transaction->setWallet($wallet);
        $this->applyInput($transaction, $input);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->auditCreated($transaction);
        $this->eventDispatcher->dispatch(new PersonalFinanceTransactionSavedEvent($transaction, isNew: true));

        return $transaction;
    }

    public function update(PersonalFinanceTransactionInterface $transaction, PersonalFinanceTransactionInputInterface $input): void
    {
        $this->ensureMutableLeg($transaction, 'update');

        $previousCategoryId = $transaction->getCategory()?->getId();

        $this->applyInput($transaction, $input);
        $this->entityManager->flush();

        $this->auditUpdated($transaction);
        $this->eventDispatcher->dispatch(new PersonalFinanceTransactionSavedEvent($transaction, isNew: false, previousCategoryId: $previousCategoryId));
    }

    public function delete(PersonalFinanceTransactionInterface $transaction): void
    {
        $this->ensureMutableLeg($transaction, 'delete');

        $this->auditDeleted($transaction);

        $transactionId = $transaction->getId();
        $hadAttachment = $transaction->hasAttachment();
        $user = $transaction->getUser();
        $categoryId = $transaction->getCategory()?->getId();
        $walletId = (int) $transaction->getWallet()->getId();

        $this->entityManager->remove($transaction);
        $this->entityManager->flush();

        if ($hadAttachment && null !== $transactionId) {
            $this->attachmentService->purgeDirectory($transactionId);
        }

        $this->eventDispatcher->dispatch(new PersonalFinanceTransactionDeletedEvent($user, $categoryId, $walletId));
    }

    /**
     * Transactions belonging to a transfer (`transferId`) or a split
     * (`splitId`) must be edited/deleted via their respective services
     * to keep grouped legs in sync. Direct mutation through the standard
     * CRUD path would leave the group in an inconsistent state.
     */
    protected function ensureMutableLeg(PersonalFinanceTransactionInterface $transaction, string $action): void
    {
        if (null !== $transaction->getTransferId()) {
            throw new DomainException(sprintf('Cannot %s a transfer transaction directly. Use PersonalFinanceTransferService instead.', $action));
        }

        if (null !== $transaction->getSplitId()) {
            throw new DomainException(sprintf('Cannot %s a split transaction directly. Use PersonalFinanceSplitService instead.', $action));
        }
    }

    protected function createTransaction(): PersonalFinanceTransactionInterface
    {
        return new PersonalFinanceTransaction();
    }

    protected function applyInput(PersonalFinanceTransactionInterface $transaction, PersonalFinanceTransactionInputInterface $input): void
    {
        $transaction->setType($input->getType());
        $transaction->setAmount($input->getAmount());
        $transaction->setDate($input->getDate());
        $transaction->setDescription($input->getDescription());
        $transaction->setTags($input->getTags());

        $category = null;
        if (null !== $input->getCategoryId()) {
            $category = $this->categoryRepository->find($input->getCategoryId());
            if (!$category instanceof PersonalFinanceCategoryInterface
                || $category->getWallet()->getId() !== $transaction->getWallet()->getId()) {
                $category = null;
            }
        }

        $transaction->setCategory($category);
    }

    protected function auditCreated(PersonalFinanceTransactionInterface $transaction): void
    {
        $this->auditLogger->log('personal_finance', 'transaction.created', 'PersonalFinanceTransaction', $transaction->getId(), $this->auditPayload($transaction));
    }

    protected function auditUpdated(PersonalFinanceTransactionInterface $transaction): void
    {
        $this->auditLogger->log('personal_finance', 'transaction.updated', 'PersonalFinanceTransaction', $transaction->getId(), $this->auditPayload($transaction));
    }

    protected function auditDeleted(PersonalFinanceTransactionInterface $transaction): void
    {
        $this->auditLogger->log('personal_finance', 'transaction.deleted', 'PersonalFinanceTransaction', $transaction->getId(), $this->auditPayload($transaction));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceTransactionInterface $transaction): array
    {
        return [
            'walletId' => $transaction->getWallet()->getId(),
            'categoryId' => $transaction->getCategory()?->getId(),
            'type' => $transaction->getType()->value,
            'amount' => $transaction->getAmount(),
            'date' => $transaction->getDate()->format('Y-m-d'),
        ];
    }
}

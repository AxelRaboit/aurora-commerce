<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInputInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceTransactionManagerInterface::class)]
class PersonalFinanceTransactionManager implements PersonalFinanceTransactionManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
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

        return $transaction;
    }

    public function update(PersonalFinanceTransactionInterface $transaction, PersonalFinanceTransactionInputInterface $input): void
    {
        $this->applyInput($transaction, $input);
        $this->entityManager->flush();

        $this->auditUpdated($transaction);
    }

    public function delete(PersonalFinanceTransactionInterface $transaction): void
    {
        $this->auditDeleted($transaction);

        $this->entityManager->remove($transaction);
        $this->entityManager->flush();
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

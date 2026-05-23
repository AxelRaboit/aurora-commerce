<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceScheduledTransactionInputInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInputFactoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Manager\PersonalFinanceTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceScheduledTransactionManagerInterface::class)]
class PersonalFinanceScheduledTransactionManager implements PersonalFinanceScheduledTransactionManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceWalletRepository $walletRepository,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
        protected readonly PersonalFinanceTransactionManagerInterface $transactionManager,
        protected readonly PersonalFinanceTransactionInputFactoryInterface $transactionInputFactory,
    ) {}

    public function create(CoreUserInterface $user, PersonalFinanceScheduledTransactionInputInterface $input): PersonalFinanceScheduledTransactionInterface
    {
        $sched = $this->createScheduled();
        $sched->setUser($user);
        $this->applyInput($sched, $input);

        $this->entityManager->persist($sched);
        $this->entityManager->flush();

        $this->auditCreated($sched);

        return $sched;
    }

    public function update(PersonalFinanceScheduledTransactionInterface $sched, PersonalFinanceScheduledTransactionInputInterface $input): void
    {
        if ($sched->isGenerated()) {
            throw new DomainException('Cannot update an already-materialized scheduled transaction.');
        }

        $this->applyInput($sched, $input);
        $this->entityManager->flush();

        $this->auditUpdated($sched);
    }

    public function delete(PersonalFinanceScheduledTransactionInterface $sched): void
    {
        $this->auditDeleted($sched);
        $this->entityManager->remove($sched);
        $this->entityManager->flush();
    }

    public function materialize(PersonalFinanceScheduledTransactionInterface $sched): PersonalFinanceTransactionInterface
    {
        if ($sched->isGenerated()) {
            throw new DomainException('Scheduled transaction has already been materialized.');
        }

        $input = $this->transactionInputFactory->fromArray([
            'type' => $sched->getType()->value,
            'amount' => $sched->getAmount(),
            'date' => $sched->getScheduledDate()->format('Y-m-d'),
            'description' => $sched->getDescription(),
            'categoryId' => $sched->getCategory()?->getId(),
        ]);

        $tx = $this->transactionManager->create($sched->getUser(), $sched->getWallet(), $input);

        $sched->setGenerated(true);
        $this->entityManager->flush();

        $this->auditMaterialized($sched, $tx);

        return $tx;
    }

    /**
     * Hook: instantiate the concrete scheduled class.
     */
    protected function createScheduled(): PersonalFinanceScheduledTransactionInterface
    {
        return new PersonalFinanceScheduledTransaction();
    }

    protected function applyInput(PersonalFinanceScheduledTransactionInterface $sched, PersonalFinanceScheduledTransactionInputInterface $input): void
    {
        $wallet = $this->walletRepository->find($input->getWalletId());
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            throw new DomainException('Wallet not found for scheduled transaction.');
        }

        $sched->setWallet($wallet);
        $sched->setCategory($this->resolveCategory($wallet, $input->getCategoryId()));
        $sched->setType($input->getType());
        $sched->setAmount($input->getAmount());
        $sched->setDescription($input->getDescription());
        $sched->setScheduledDate($input->getScheduledDate());
    }

    protected function resolveCategory(PersonalFinanceWalletInterface $wallet, ?int $categoryId): ?PersonalFinanceCategoryInterface
    {
        if (null === $categoryId) {
            return null;
        }

        $category = $this->categoryRepository->find($categoryId);
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return null;
        }

        if ($category->getWallet()->getId() !== $wallet->getId()) {
            return null;
        }

        return $category;
    }

    protected function auditCreated(PersonalFinanceScheduledTransactionInterface $sched): void
    {
        $this->auditLogger->log('personal_finance', 'scheduled.created', 'PersonalFinanceScheduledTransaction', $sched->getId(), $this->auditPayload($sched));
    }

    protected function auditUpdated(PersonalFinanceScheduledTransactionInterface $sched): void
    {
        $this->auditLogger->log('personal_finance', 'scheduled.updated', 'PersonalFinanceScheduledTransaction', $sched->getId(), $this->auditPayload($sched));
    }

    protected function auditDeleted(PersonalFinanceScheduledTransactionInterface $sched): void
    {
        $this->auditLogger->log('personal_finance', 'scheduled.deleted', 'PersonalFinanceScheduledTransaction', $sched->getId(), $this->auditPayload($sched));
    }

    protected function auditMaterialized(PersonalFinanceScheduledTransactionInterface $sched, PersonalFinanceTransactionInterface $tx): void
    {
        $this->auditLogger->log('personal_finance', 'scheduled.materialized', 'PersonalFinanceScheduledTransaction', $sched->getId(), [
            ...$this->auditPayload($sched),
            'transactionId' => $tx->getId(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceScheduledTransactionInterface $sched): array
    {
        return [
            'walletId' => $sched->getWallet()->getId(),
            'categoryId' => $sched->getCategory()?->getId(),
            'type' => $sched->getType()->value,
            'amount' => $sched->getAmount(),
            'scheduledDate' => $sched->getScheduledDate()->format('Y-m-d'),
            'generated' => $sched->isGenerated(),
        ];
    }
}

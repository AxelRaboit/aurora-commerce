<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Split\Service;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionDeletedEvent;
use Aurora\Module\PersonalFinance\Transaction\Event\PersonalFinanceTransactionSavedEvent;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Split\Dto\PersonalFinanceSplitInputInterface;
use Aurora\Module\PersonalFinance\Transaction\Split\Dto\PersonalFinanceSplitPart;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DomainException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(PersonalFinanceSplitServiceInterface::class)]
class PersonalFinanceSplitService implements PersonalFinanceSplitServiceInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
        protected readonly PersonalFinanceTransactionRepository $transactionRepository,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function create(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceSplitInputInterface $input,
    ): string {
        $parts = $input->getParts();
        if (count($parts) < 2) {
            throw new DomainException('A split requires at least 2 parts.');
        }

        $splitId = Uuid::v7()->toRfc4122();
        $created = [];

        $this->entityManager->wrapInTransaction(function () use ($user, $wallet, $input, $parts, $splitId, &$created): void {
            foreach ($parts as $part) {
                $category = $this->resolveCategory($wallet, $part);
                $transaction = $this->buildPart($user, $wallet, $input, $part, $category, $splitId);
                $this->entityManager->persist($transaction);
                $created[] = $transaction;
            }

            $this->entityManager->flush();

            $this->auditCreated($splitId, $wallet, $created);
        });

        foreach ($created as $leg) {
            $this->eventDispatcher->dispatch(new PersonalFinanceTransactionSavedEvent($leg, isNew: true));
        }

        return $splitId;
    }

    public function delete(string $splitId): void
    {
        $transactions = $this->transactionRepository->findBySplitId($splitId);
        if ([] === $transactions) {
            throw new DomainException(sprintf('Split %s not found.', $splitId));
        }

        $snapshots = array_map(
            static fn (PersonalFinanceTransactionInterface $tx): array => [
                'user' => $tx->getUser(),
                'categoryId' => $tx->getCategory()?->getId(),
                'walletId' => (int) $tx->getWallet()->getId(),
            ],
            $transactions,
        );

        $this->entityManager->wrapInTransaction(function () use ($splitId, $transactions): void {
            $this->auditDeleted($splitId, $transactions);

            foreach ($transactions as $tx) {
                $this->entityManager->remove($tx);
            }
            $this->entityManager->flush();
        });

        foreach ($snapshots as $snap) {
            $this->eventDispatcher->dispatch(new PersonalFinanceTransactionDeletedEvent($snap['user'], $snap['categoryId'], $snap['walletId']));
        }
    }

    /**
     * Hook: instantiate the concrete transaction class. Override in a
     * client subclass to return your extended entity (mirrors
     * PersonalFinanceTransactionManager::createTransaction).
     */
    protected function createTransaction(): PersonalFinanceTransactionInterface
    {
        return new PersonalFinanceTransaction();
    }

    protected function resolveCategory(PersonalFinanceWalletInterface $wallet, PersonalFinanceSplitPart $part): ?PersonalFinanceCategoryInterface
    {
        if (null === $part->categoryId) {
            return null;
        }
        $category = $this->categoryRepository->find($part->categoryId);
        if (!$category instanceof PersonalFinanceCategoryInterface
            || $category->getWallet()->getId() !== $wallet->getId()) {
            throw new DomainException(sprintf('Category %d does not belong to wallet %d.', $part->categoryId, (int) $wallet->getId()));
        }

        return $category;
    }

    protected function buildPart(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceSplitInputInterface $input,
        PersonalFinanceSplitPart $part,
        ?PersonalFinanceCategoryInterface $category,
        string $splitId,
    ): PersonalFinanceTransactionInterface {
        $tx = $this->createTransaction();
        $tx->setUser($user);
        $tx->setWallet($wallet);
        $tx->setCategory($category);
        $tx->setType($input->getType());
        $tx->setAmount($part->amount);
        $tx->setDate($input->getDate());
        $tx->setDescription($part->description ?? $input->getDescription());
        $tx->setTags($input->getTags());
        $tx->setSplitId($splitId);

        return $tx;
    }

    /** @param list<PersonalFinanceTransactionInterface> $transactions */
    protected function auditCreated(string $splitId, PersonalFinanceWalletInterface $wallet, array $transactions): void
    {
        $this->auditLogger->log('personal_finance', 'split.created', 'PersonalFinanceSplit', null, $this->auditPayload($splitId, $wallet, $transactions));
    }

    /** @param list<PersonalFinanceTransactionInterface> $transactions */
    protected function auditDeleted(string $splitId, array $transactions): void
    {
        $wallet = $transactions[0]?->getWallet();
        $this->auditLogger->log('personal_finance', 'split.deleted', 'PersonalFinanceSplit', null, $this->auditPayload($splitId, $wallet, $transactions));
    }

    /**
     * @param list<PersonalFinanceTransactionInterface> $transactions
     *
     * @return array<string, mixed>
     */
    protected function auditPayload(string $splitId, ?PersonalFinanceWalletInterface $wallet, array $transactions): array
    {
        $total = '0';
        foreach ($transactions as $tx) {
            $total = bcadd($total, $tx->getAmount(), 2);
        }

        return [
            'splitId' => $splitId,
            'walletId' => $wallet?->getId(),
            'parts' => count($transactions),
            'total' => $total,
            'transactionIds' => array_map(static fn (PersonalFinanceTransactionInterface $tx): ?int => $tx->getId(), $transactions),
        ];
    }
}

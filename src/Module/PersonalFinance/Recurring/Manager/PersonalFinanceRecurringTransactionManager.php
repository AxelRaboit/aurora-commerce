<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Recurring\Dto\PersonalFinanceRecurringTransactionInputInterface;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInputFactoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Manager\PersonalFinanceTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceRecurringTransactionManagerInterface::class)]
class PersonalFinanceRecurringTransactionManager implements PersonalFinanceRecurringTransactionManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceWalletRepository $walletRepository,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
        protected readonly PersonalFinanceTransactionManagerInterface $transactionManager,
        protected readonly PersonalFinanceTransactionInputFactoryInterface $transactionInputFactory,
    ) {}

    public function create(CoreUserInterface $user, PersonalFinanceRecurringTransactionInputInterface $input): PersonalFinanceRecurringTransactionInterface
    {
        $rec = $this->createRecurring();
        $rec->setUser($user);
        $this->applyInput($rec, $input);

        $this->entityManager->persist($rec);
        $this->entityManager->flush();

        $this->auditCreated($rec);

        return $rec;
    }

    public function update(PersonalFinanceRecurringTransactionInterface $rec, PersonalFinanceRecurringTransactionInputInterface $input): void
    {
        $this->applyInput($rec, $input);
        $this->entityManager->flush();

        $this->auditUpdated($rec);
    }

    public function delete(PersonalFinanceRecurringTransactionInterface $rec): void
    {
        $this->auditDeleted($rec);
        $this->entityManager->remove($rec);
        $this->entityManager->flush();
    }

    public function toggle(PersonalFinanceRecurringTransactionInterface $rec): void
    {
        $rec->setActive(!$rec->isActive());
        $this->entityManager->flush();

        $this->auditUpdated($rec);

        if ($rec->isActive()) {
            $this->generateIfDue($rec);
        }
    }

    public function generateIfDue(PersonalFinanceRecurringTransactionInterface $rec, ?DateTimeImmutable $today = null): ?PersonalFinanceTransactionInterface
    {
        $today ??= new DateTimeImmutable('today');

        if (!$this->shouldGenerate($rec, $today)) {
            return null;
        }

        $date = $today->setDate((int) $today->format('Y'), (int) $today->format('n'), $rec->getDayOfMonth())->setTime(0, 0);

        $input = $this->transactionInputFactory->fromArray([
            'type' => $rec->getType()->value,
            'amount' => $rec->getAmount(),
            'date' => $date->format('Y-m-d'),
            'description' => $rec->getDescription(),
            'categoryId' => $rec->getCategory()?->getId(),
        ]);

        $tx = $this->transactionManager->create($rec->getUser(), $rec->getWallet(), $input);

        $rec->setLastGeneratedAt($today);
        $this->entityManager->flush();

        $this->auditGenerated($rec, $tx);

        return $tx;
    }

    /**
     * Hook: instantiate the concrete recurring class.
     */
    protected function createRecurring(): PersonalFinanceRecurringTransactionInterface
    {
        return new PersonalFinanceRecurringTransaction();
    }

    /**
     * Hook: gate the generation. Override to add custom rules (skip
     * weekends, holidays, etc.). Default: active + dayOfMonth passed +
     * not yet generated this month.
     */
    protected function shouldGenerate(PersonalFinanceRecurringTransactionInterface $rec, DateTimeImmutable $today): bool
    {
        if (!$rec->isActive()) {
            return false;
        }

        if ($rec->getDayOfMonth() > (int) $today->format('j')) {
            return false;
        }

        return $rec->getLastGeneratedAt()?->format('Y-m') !== $today->format('Y-m');
    }

    protected function applyInput(PersonalFinanceRecurringTransactionInterface $rec, PersonalFinanceRecurringTransactionInputInterface $input): void
    {
        $wallet = $this->walletRepository->find($input->getWalletId());
        if (!$wallet instanceof PersonalFinanceWalletInterface) {
            throw new DomainException('Wallet not found for recurring transaction.');
        }

        $rec->setWallet($wallet);
        $rec->setCategory($this->resolveCategory($wallet, $input->getCategoryId()));
        $rec->setType($input->getType());
        $rec->setAmount($input->getAmount());
        $rec->setDescription($input->getDescription());
        $rec->setDayOfMonth($input->getDayOfMonth());
        $rec->setActive($input->isActive());
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

    protected function auditCreated(PersonalFinanceRecurringTransactionInterface $rec): void
    {
        $this->auditLogger->log('personal_finance', 'recurring.created', 'PersonalFinanceRecurringTransaction', $rec->getId(), $this->auditPayload($rec));
    }

    protected function auditUpdated(PersonalFinanceRecurringTransactionInterface $rec): void
    {
        $this->auditLogger->log('personal_finance', 'recurring.updated', 'PersonalFinanceRecurringTransaction', $rec->getId(), $this->auditPayload($rec));
    }

    protected function auditDeleted(PersonalFinanceRecurringTransactionInterface $rec): void
    {
        $this->auditLogger->log('personal_finance', 'recurring.deleted', 'PersonalFinanceRecurringTransaction', $rec->getId(), $this->auditPayload($rec));
    }

    protected function auditGenerated(PersonalFinanceRecurringTransactionInterface $rec, PersonalFinanceTransactionInterface $tx): void
    {
        $this->auditLogger->log('personal_finance', 'recurring.generated', 'PersonalFinanceRecurringTransaction', $rec->getId(), [
            ...$this->auditPayload($rec),
            'transactionId' => $tx->getId(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceRecurringTransactionInterface $rec): array
    {
        return [
            'walletId' => $rec->getWallet()->getId(),
            'categoryId' => $rec->getCategory()?->getId(),
            'type' => $rec->getType()->value,
            'amount' => $rec->getAmount(),
            'dayOfMonth' => $rec->getDayOfMonth(),
            'active' => $rec->isActive(),
        ];
    }
}

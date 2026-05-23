<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
use Aurora\Module\PersonalFinance\Budget\Service\PersonalFinanceBudgetRolloverServiceInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetManagerInterface::class)]
class PersonalFinanceBudgetManager implements PersonalFinanceBudgetManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceBudgetRepository $budgetRepository,
        protected readonly PersonalFinanceBudgetRolloverServiceInterface $rollover,
    ) {}

    public function ensureForMonth(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        DateTimeImmutable $month,
    ): PersonalFinanceBudgetInterface {
        $existing = $this->budgetRepository->findByWalletAndMonth($wallet, $month);
        if ($existing instanceof PersonalFinanceBudgetInterface) {
            return $existing;
        }

        $budget = $this->createBudget();
        $budget->setUser($user);
        $budget->setWallet($wallet);
        $budget->setMonth($month);

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        $this->auditCreated($budget);

        return $budget;
    }

    /**
     * Explicit rollover from the previous month's `repeatNextMonth`
     * items — triggered from a button in the Budget UI rather than
     * implicitly on `ensureForMonth`. Sets `rolledOverAt` so the UI
     * banner hides on subsequent loads.
     *
     * Returns the count of items inserted. No-op (returns 0) when
     * already rolled over, when the budget already has items, or
     * when the previous month has nothing to carry.
     */
    public function rolloverFromPrevious(PersonalFinanceBudgetInterface $budget): int
    {
        if ($budget->getRolledOverAt() instanceof DateTimeImmutable) {
            return 0;
        }

        $count = $this->rollover->rolloverFrom($budget);
        $budget->setRolledOverAt(new DateTimeImmutable());
        $this->entityManager->flush();

        if ($count > 0) {
            $this->auditRolledOver($budget, $count);
        }

        return $count;
    }

    protected function auditRolledOver(PersonalFinanceBudgetInterface $budget, int $count): void
    {
        $this->auditLogger->log(
            'personal_finance',
            'budget.rolled_over',
            'PersonalFinanceBudget',
            $budget->getId(),
            ['count' => $count] + $this->auditPayload($budget),
        );
    }

    public function updateNotes(PersonalFinanceBudgetInterface $budget, ?string $notes): void
    {
        $budget->setNotes($notes);
        $this->entityManager->flush();

        $this->auditUpdated($budget);
    }

    public function delete(PersonalFinanceBudgetInterface $budget): void
    {
        $this->auditDeleted($budget);

        $this->entityManager->remove($budget);
        $this->entityManager->flush();
    }

    protected function createBudget(): PersonalFinanceBudgetInterface
    {
        return new PersonalFinanceBudget();
    }

    protected function auditCreated(PersonalFinanceBudgetInterface $budget): void
    {
        $this->auditLogger->log('personal_finance', 'budget.created', 'PersonalFinanceBudget', $budget->getId(), $this->auditPayload($budget));
    }

    protected function auditUpdated(PersonalFinanceBudgetInterface $budget): void
    {
        $this->auditLogger->log('personal_finance', 'budget.updated', 'PersonalFinanceBudget', $budget->getId(), $this->auditPayload($budget));
    }

    protected function auditDeleted(PersonalFinanceBudgetInterface $budget): void
    {
        $this->auditLogger->log('personal_finance', 'budget.deleted', 'PersonalFinanceBudget', $budget->getId(), $this->auditPayload($budget));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceBudgetInterface $budget): array
    {
        return [
            'walletId' => $budget->getWallet()->getId(),
            'month' => $budget->getMonth()->format('Y-m'),
        ];
    }
}

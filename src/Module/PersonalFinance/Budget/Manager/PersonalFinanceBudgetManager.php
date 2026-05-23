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
    /**
     * Number of items copied by the last `ensureForMonth` call. The
     * Twig/Vue layer reads this from `lastRolloverCount()` (instead
     * of mutating the Budget entity) so the rollover stays a service
     * concern and the entity contract stays pure.
     */
    protected int $lastRolloverCount = 0;

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
        $this->lastRolloverCount = 0;

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

        $this->lastRolloverCount = $this->rollover->rolloverFrom($budget);
        if ($this->lastRolloverCount > 0) {
            $this->auditRolledOver($budget, $this->lastRolloverCount);
        }

        return $budget;
    }

    public function lastRolloverCount(): int
    {
        return $this->lastRolloverCount;
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

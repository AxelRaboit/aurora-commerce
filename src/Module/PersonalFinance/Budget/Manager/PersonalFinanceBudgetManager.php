<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
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

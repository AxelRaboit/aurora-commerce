<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Goal\Dto\PersonalFinanceGoalDepositInputInterface;
use Aurora\Module\PersonalFinance\Goal\Dto\PersonalFinanceGoalInputInterface;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoal;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceGoalManagerInterface::class)]
class PersonalFinanceGoalManager implements PersonalFinanceGoalManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceWalletRepository $walletRepository,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
        protected readonly PersonalFinanceTransactionRepository $transactionRepository,
    ) {}

    public function create(CoreUserInterface $user, PersonalFinanceGoalInputInterface $input): PersonalFinanceGoalInterface
    {
        $goal = $this->createGoal();
        $goal->setUser($user);
        $this->hydrate($goal, $input);

        $this->entityManager->persist($goal);
        $this->entityManager->flush();

        if ($goal->isAutoTracked()) {
            $this->recomputeSavedAmount($goal);
        }

        $this->auditCreated($goal);

        return $goal;
    }

    public function update(PersonalFinanceGoalInterface $goal, PersonalFinanceGoalInputInterface $input): void
    {
        $previousCategoryId = $goal->getCategory()?->getId();
        $this->hydrate($goal, $input);
        $this->entityManager->flush();

        if ($goal->getCategory()?->getId() !== $previousCategoryId) {
            if ($goal->isAutoTracked()) {
                $this->recomputeSavedAmount($goal);
            } else {
                $goal->setSavedAmount('0.00');
                $this->entityManager->flush();
            }
        }

        $this->auditUpdated($goal);
    }

    public function deposit(PersonalFinanceGoalInterface $goal, PersonalFinanceGoalDepositInputInterface $input): void
    {
        if ($goal->isAutoTracked()) {
            throw new DomainException('Cannot deposit manually on an auto-tracked goal — saved amount is computed from the linked category.');
        }

        $goal->setSavedAmount(bcadd($goal->getSavedAmount(), $input->getAmount(), 2));
        $this->entityManager->flush();

        $this->auditDeposit($goal, $input->getAmount());
    }

    public function delete(PersonalFinanceGoalInterface $goal): void
    {
        $this->auditDeleted($goal);

        $this->entityManager->remove($goal);
        $this->entityManager->flush();
    }

    public function recomputeSavedAmount(PersonalFinanceGoalInterface $goal): void
    {
        $category = $goal->getCategory();
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return;
        }

        $total = $this->transactionRepository->sumByCategoryForUser($goal->getUser(), $category);
        $goal->setSavedAmount($total);
        $this->entityManager->flush();
    }

    /**
     * Hook: instantiate the concrete goal class. Override in a client
     * subclass to return your extended entity.
     */
    protected function createGoal(): PersonalFinanceGoalInterface
    {
        return new PersonalFinanceGoal();
    }

    protected function hydrate(PersonalFinanceGoalInterface $goal, PersonalFinanceGoalInputInterface $input): void
    {
        $goal->setName($input->getName());
        $goal->setTargetAmount($input->getTargetAmount());
        $goal->setDeadline($input->getDeadline());
        $goal->setColor($input->getColor());
        $goal->setWallet($this->resolveWallet($input->getWalletId()));
        $goal->setCategory($this->resolveCategory($goal->getUser(), $input->getCategoryId()));
    }

    protected function resolveWallet(?int $walletId): ?PersonalFinanceWalletInterface
    {
        if (null === $walletId) {
            return null;
        }
        $wallet = $this->walletRepository->find($walletId);

        return $wallet instanceof PersonalFinanceWalletInterface ? $wallet : null;
    }

    protected function resolveCategory(CoreUserInterface $user, ?int $categoryId): ?PersonalFinanceCategoryInterface
    {
        if (null === $categoryId) {
            return null;
        }
        $category = $this->categoryRepository->find($categoryId);
        if (!$category instanceof PersonalFinanceCategoryInterface) {
            return null;
        }
        if ($category->isSystem()) {
            return null;
        }

        return $category;
    }

    protected function auditCreated(PersonalFinanceGoalInterface $goal): void
    {
        $this->auditLogger->log('personal_finance', 'goal.created', 'PersonalFinanceGoal', $goal->getId(), $this->auditPayload($goal));
    }

    protected function auditUpdated(PersonalFinanceGoalInterface $goal): void
    {
        $this->auditLogger->log('personal_finance', 'goal.updated', 'PersonalFinanceGoal', $goal->getId(), $this->auditPayload($goal));
    }

    protected function auditDeleted(PersonalFinanceGoalInterface $goal): void
    {
        $this->auditLogger->log('personal_finance', 'goal.deleted', 'PersonalFinanceGoal', $goal->getId(), $this->auditPayload($goal));
    }

    protected function auditDeposit(PersonalFinanceGoalInterface $goal, string $amount): void
    {
        $this->auditLogger->log('personal_finance', 'goal.deposit', 'PersonalFinanceGoal', $goal->getId(), [
            ...$this->auditPayload($goal),
            'depositAmount' => $amount,
        ]);
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceGoalInterface $goal): array
    {
        return [
            'name' => $goal->getName(),
            'targetAmount' => $goal->getTargetAmount(),
            'savedAmount' => $goal->getSavedAmount(),
            'categoryId' => $goal->getCategory()?->getId(),
            'walletId' => $goal->getWallet()?->getId(),
            'deadline' => $goal->getDeadline()?->format('Y-m-d'),
        ];
    }
}

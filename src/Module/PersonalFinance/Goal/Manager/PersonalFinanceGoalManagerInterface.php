<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Manager;

use Aurora\Module\PersonalFinance\Goal\Dto\PersonalFinanceGoalDepositInputInterface;
use Aurora\Module\PersonalFinance\Goal\Dto\PersonalFinanceGoalInputInterface;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceGoalManagerInterface
{
    public function create(CoreUserInterface $user, PersonalFinanceGoalInputInterface $input): PersonalFinanceGoalInterface;

    public function update(PersonalFinanceGoalInterface $goal, PersonalFinanceGoalInputInterface $input): void;

    /**
     * Manual deposit on a goal that is NOT auto-tracked. For auto-tracked
     * goals (category-linked), this throws DomainException — the sync
     * happens automatically through PersonalFinanceGoalSyncSubscriber
     * whenever a transaction in the tracked category is saved.
     */
    public function deposit(PersonalFinanceGoalInterface $goal, PersonalFinanceGoalDepositInputInterface $input): void;

    public function delete(PersonalFinanceGoalInterface $goal): void;

    /**
     * Recomputes savedAmount as the sum of |amount| of every transaction
     * in the goal's category, excluding transfer legs. Used by the event
     * subscriber on transaction save/delete. No-op for goals without a
     * linked category.
     */
    public function recomputeSavedAmount(PersonalFinanceGoalInterface $goal): void;
}

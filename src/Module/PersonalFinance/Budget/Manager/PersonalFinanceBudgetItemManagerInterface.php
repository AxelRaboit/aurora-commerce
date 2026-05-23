<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceBudgetItemInputInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;

interface PersonalFinanceBudgetItemManagerInterface
{
    public function create(PersonalFinanceBudgetInterface $budget, PersonalFinanceBudgetItemInputInterface $input): PersonalFinanceBudgetItemInterface;

    public function update(PersonalFinanceBudgetItemInterface $item, PersonalFinanceBudgetItemInputInterface $input): void;

    public function delete(PersonalFinanceBudgetItemInterface $item): void;
}

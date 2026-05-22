<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Manager;

use Aurora\Module\PersonalFinance\Category\Dto\PersonalFinanceCategoryInputInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Enum\PersonalFinanceSystemCategoryKeyEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceCategoryManagerInterface
{
    public function create(CoreUserInterface $user, PersonalFinanceWalletInterface $wallet, PersonalFinanceCategoryInputInterface $input): PersonalFinanceCategoryInterface;

    public function update(PersonalFinanceCategoryInterface $category, PersonalFinanceCategoryInputInterface $input): void;

    public function delete(PersonalFinanceCategoryInterface $category): void;

    public function getOrCreateSystem(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceSystemCategoryKeyEnum|string $systemKey,
        string $defaultName,
    ): PersonalFinanceCategoryInterface;
}

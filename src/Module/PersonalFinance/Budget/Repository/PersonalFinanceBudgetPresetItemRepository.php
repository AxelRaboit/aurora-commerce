<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItemInterface;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceBudgetPresetItemInterface> */
class PersonalFinanceBudgetPresetItemRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceBudgetPresetItem::class, PersonalFinanceBudgetPresetItemInterface::class);
    }
}

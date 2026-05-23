<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceScheduledTransactionInterface> */
class PersonalFinanceScheduledTransactionRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceScheduledTransaction::class, PersonalFinanceScheduledTransactionInterface::class);
    }

    /**
     * @return list<PersonalFinanceScheduledTransactionInterface>
     */
    public function findOwnedByUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.wallet', 'w')->addSelect('w')
            ->leftJoin('s.category', 'c')->addSelect('c')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.scheduledDate', Order::Ascending->value)
            ->addOrderBy('s.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPreset;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceBudgetPresetInterface> */
class PersonalFinanceBudgetPresetRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceBudgetPreset::class, PersonalFinanceBudgetPresetInterface::class);
    }

    /**
     * Lists presets attached to a wallet, ordered by name (alphabetical
     * is the natural one — list size is expected to stay small per
     * wallet, so no pagination needed).
     *
     * @return list<PersonalFinanceBudgetPresetInterface>
     */
    public function findByWallet(PersonalFinanceWalletInterface $wallet): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.items', 'i')
            ->addSelect('i')
            ->where('p.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->orderBy('LOWER(p.name)', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

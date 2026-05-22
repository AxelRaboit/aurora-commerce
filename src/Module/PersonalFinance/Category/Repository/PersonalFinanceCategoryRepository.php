<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategory;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceCategoryInterface> */
class PersonalFinanceCategoryRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceCategory::class, PersonalFinanceCategoryInterface::class);
    }

    /**
     * Paginated list of user-created (non-system) categories for the wallet.
     *
     * @return array{items: list<PersonalFinanceCategoryInterface>, total: int, page: int, totalPages: int}
     */
    public function findPaginatedUserCategoriesByWallet(PersonalFinanceWalletInterface $wallet, int $page, int $limit = 20, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.wallet = :wallet')
            ->andWhere('c.isSystem = false')
            ->setParameter('wallet', $wallet)
            ->orderBy('c.name', Order::Ascending->value);

        $countQb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.wallet = :wallet')
            ->andWhere('c.isSystem = false')
            ->setParameter('wallet', $wallet);

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /**
     * Returns user-created (non-system) categories for the wallet.
     *
     * @return list<PersonalFinanceCategoryInterface>
     */
    public function findUserCategoriesByWallet(PersonalFinanceWalletInterface $wallet): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.wallet = :wallet')
            ->andWhere('c.isSystem = false')
            ->setParameter('wallet', $wallet)
            ->orderBy('c.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    public function findSystemByKey(PersonalFinanceWalletInterface $wallet, string $systemKey): ?PersonalFinanceCategoryInterface
    {
        return $this->createQueryBuilder('c')
            ->where('c.wallet = :wallet')
            ->andWhere('c.systemKey = :systemKey')
            ->setParameter('wallet', $wallet)
            ->setParameter('systemKey', $systemKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByWalletAndName(PersonalFinanceWalletInterface $wallet, string $name): ?PersonalFinanceCategoryInterface
    {
        return $this->createQueryBuilder('c')
            ->where('c.wallet = :wallet')
            ->andWhere('LOWER(c.name) = LOWER(:name)')
            ->andWhere('c.isSystem = false')
            ->setParameter('wallet', $wallet)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

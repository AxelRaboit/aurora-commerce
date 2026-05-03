<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tiers>
 */
class TiersRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tiers::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?TiersTypeEnum $type = null): array
    {
        $qb = $this->createQueryBuilder('t')->orderBy('t.name', Order::Ascending->value);
        $countQb = $this->createQueryBuilder('t')->select('COUNT(t.id)');

        if ($type instanceof TiersTypeEnum) {
            $qb->andWhere('t.type = :type')->setParameter('type', $type);
            $countQb->andWhere('t.type = :type')->setParameter('type', $type);
        }

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(t.name) LIKE :search OR LOWER(t.vatNumber) LIKE :search')
                ->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(t.name) LIKE :search OR LOWER(t.vatNumber) LIKE :search')
                ->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    public function findOneByVatNumber(string $vatNumber): ?Tiers
    {
        return $this->findOneBy(['vatNumber' => $vatNumber]);
    }

    public function findOneByNameLike(string $name, ?TiersTypeEnum $type = null): ?Tiers
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1);

        if ($type instanceof TiersTypeEnum) {
            $qb->andWhere('t.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}

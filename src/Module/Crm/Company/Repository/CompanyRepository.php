<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<CompanyInterface> */
class CompanyRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class, CompanyInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.name', Order::Ascending->value);
        $countQb = $this->createQueryBuilder('c')->select('COUNT(c.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.industry) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.industry) LIKE :search')->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return list<CompanyInterface> */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

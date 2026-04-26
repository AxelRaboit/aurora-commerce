<?php

declare(strict_types=1);

namespace App\Module\Crm\Company\Repository;

use App\Core\Repository\Trait\PaginationTrait;
use App\Module\Crm\Company\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Company> */
class CompanyRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
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
}

<?php

declare(strict_types=1);

namespace App\Module\Crm\Contact\Repository;

use App\Core\Repository\Trait\PaginationTrait;
use App\Module\Crm\Contact\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?int $companyId = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.company', 'co')->addSelect('co')
            ->orderBy('c.lastName', Order::Ascending->value)
            ->addOrderBy('c.firstName', Order::Ascending->value);

        $countQb = $this->createQueryBuilder('c')->select('COUNT(c.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(c.firstName) LIKE :search OR LOWER(c.lastName) LIKE :search OR LOWER(c.email) LIKE :search OR LOWER(co.name) LIKE :search')
                ->setParameter('search', $pattern);
            $countQb->leftJoin('c.company', 'co2')
                ->andWhere('LOWER(c.firstName) LIKE :search OR LOWER(c.lastName) LIKE :search OR LOWER(c.email) LIKE :search OR LOWER(co2.name) LIKE :search')
                ->setParameter('search', $pattern);
        }

        if (null !== $companyId) {
            $qb->andWhere('c.company = :companyId')->setParameter('companyId', $companyId);
            $countQb->andWhere('c.company = :companyId')->setParameter('companyId', $companyId);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }
}

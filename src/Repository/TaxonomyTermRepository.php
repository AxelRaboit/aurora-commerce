<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Taxonomy;
use App\Entity\TaxonomyTerm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaxonomyTerm>
 */
class TaxonomyTermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxonomyTerm::class);
    }

    /**
     * @return list<TaxonomyTerm>
     */
    public function findByTaxonomyOrdered(Taxonomy $taxonomy): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.taxonomy = :taxonomy')
            ->setParameter('taxonomy', $taxonomy)
            ->orderBy('t.position', Order::Ascending->value)
            ->addOrderBy('t.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

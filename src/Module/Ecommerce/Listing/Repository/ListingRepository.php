<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Erp\Product\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Listing>
 */
class ListingRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?bool $visibleOnly = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.product', 'p')->addSelect('p')
            ->orderBy('l.createdAt', Order::Descending->value);

        $countQb = $this->createQueryBuilder('l')->select('COUNT(l.id)')
            ->leftJoin('l.product', 'p');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(l.slug) LIKE :search OR LOWER(l.marketingTitle) LIKE :search OR LOWER(p.name) LIKE :search OR LOWER(p.reference) LIKE :search')
                ->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(l.slug) LIKE :search OR LOWER(l.marketingTitle) LIKE :search OR LOWER(p.name) LIKE :search OR LOWER(p.reference) LIKE :search')
                ->setParameter('search', $pattern);
        }

        if (true === $visibleOnly) {
            $qb->andWhere('l.isVisibleOnShop = :visible')->setParameter('visible', true);
            $countQb->andWhere('l.isVisibleOnShop = :visible')->setParameter('visible', true);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    public function findOneBySlug(string $slug): ?Listing
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findOneByProduct(Product $product): ?Listing
    {
        return $this->findOneBy(['product' => $product]);
    }
}

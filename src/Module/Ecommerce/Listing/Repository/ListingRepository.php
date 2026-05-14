<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ListingInterface>
 */
class ListingRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class, ListingInterface::class);
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

    public function findOneBySlug(string $slug): ?ListingInterface
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findOneByProduct(ProductInterface $product): ?ListingInterface
    {
        return $this->findOneBy(['product' => $product]);
    }

    /**
     * Returns visible listings attached to any of the given category ids.
     *
     * DISTINCT is required because a listing may belong to multiple categories in
     * the requested set (e.g. a parent and one of its descendants). Ordering uses
     * marketingTitle ASC and falls back to id DESC for stability.
     *
     * @param list<int> $categoryIds
     *
     * @return array{items: list<ListingInterface>, total: int, page: int, perPage: int}
     */
    public function findVisibleByCategoryIdsPaginated(array $categoryIds, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        if ([] === $categoryIds) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage];
        }

        $itemsQb = $this->createQueryBuilder('l')
            ->distinct()
            ->leftJoin('l.product', 'p')->addSelect('p')
            ->innerJoin('l.categories', 'c')
            ->andWhere('c.id IN (:categoryIds)')
            ->andWhere('l.isVisibleOnShop = :visible')
            ->setParameter('categoryIds', $categoryIds)
            ->setParameter('visible', true)
            ->orderBy('l.marketingTitle', Order::Ascending->value)
            ->addOrderBy('l.id', Order::Descending->value)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $countQb = $this->createQueryBuilder('l')
            ->select('COUNT(DISTINCT l.id)')
            ->innerJoin('l.categories', 'c')
            ->andWhere('c.id IN (:categoryIds)')
            ->andWhere('l.isVisibleOnShop = :visible')
            ->setParameter('categoryIds', $categoryIds)
            ->setParameter('visible', true);

        /** @var list<ListingInterface> $items */
        $items = array_values($itemsQb->getQuery()->getResult());
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $this->hydrateCategoriesAndTags($items);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    /**
     * Returns visible listings attached to any of the given tag ids.
     *
     * Tags are flat (no descendants), so the caller passes either a single tag
     * id or an arbitrary set. DISTINCT guards against duplicate rows when a
     * listing carries multiple matching tags. Same ordering contract as
     * {@see self::findVisibleByCategoryIdsPaginated()}: marketingTitle ASC,
     * id DESC as a stable tiebreaker.
     *
     * @param list<int> $tagIds
     *
     * @return array{items: list<ListingInterface>, total: int, page: int, perPage: int}
     */
    public function findVisibleByTagIdsPaginated(array $tagIds, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        if ([] === $tagIds) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage];
        }

        $itemsQb = $this->createQueryBuilder('l')
            ->distinct()
            ->leftJoin('l.product', 'p')->addSelect('p')
            ->innerJoin('l.tags', 't')
            ->andWhere('t.id IN (:tagIds)')
            ->andWhere('l.isVisibleOnShop = :visible')
            ->setParameter('tagIds', $tagIds)
            ->setParameter('visible', true)
            ->orderBy('l.marketingTitle', Order::Ascending->value)
            ->addOrderBy('l.id', Order::Descending->value)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $countQb = $this->createQueryBuilder('l')
            ->select('COUNT(DISTINCT l.id)')
            ->innerJoin('l.tags', 't')
            ->andWhere('t.id IN (:tagIds)')
            ->andWhere('l.isVisibleOnShop = :visible')
            ->setParameter('tagIds', $tagIds)
            ->setParameter('visible', true);

        /** @var list<ListingInterface> $items */
        $items = array_values($itemsQb->getQuery()->getResult());
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $this->hydrateCategoriesAndTags($items);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    /**
     * Eager-hydrates categories+translations and tags+translations for the given
     * listings via separate queries (one for categories, one for tags) to avoid
     * N+1 on serialization. We do this in a second pass rather than in the main
     * query because joining ManyToMany twice on the same row produces a cartesian
     * product (categories x tags) that breaks pagination via setMaxResults().
     *
     * @param list<ListingInterface> $listings
     */
    private function hydrateCategoriesAndTags(array $listings): void
    {
        if ([] === $listings) {
            return;
        }

        $ids = array_values(array_filter(array_map(static fn (ListingInterface $listing): ?int => $listing->getId(), $listings)));
        if ([] === $ids) {
            return;
        }

        $this->createQueryBuilder('l')
            ->select('PARTIAL l.{id}', 'c', 'ct')
            ->leftJoin('l.categories', 'c')
            ->leftJoin('c.translations', 'ct')
            ->andWhere('l.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('l')
            ->select('PARTIAL l.{id}', 't', 'tt')
            ->leftJoin('l.tags', 't')
            ->leftJoin('t.translations', 'tt')
            ->andWhere('l.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}

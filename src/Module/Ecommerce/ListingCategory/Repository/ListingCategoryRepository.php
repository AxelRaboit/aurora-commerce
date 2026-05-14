<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ListingCategoryInterface>
 */
class ListingCategoryRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListingCategory::class, ListingCategoryInterface::class);
    }

    /** @return list<ListingCategoryInterface> */
    public function findRoots(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return list<ListingCategoryInterface> */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(string $slug, string $locale): ?ListingCategoryInterface
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.translations', 't')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.slug = :slug')
            ->setParameter('locale', $locale)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<ListingCategoryInterface> */
    public function findDescendantsOf(ListingCategoryInterface $category): array
    {
        $descendants = [];
        $stack = iterator_to_array($category->getChildren());
        while ([] !== $stack) {
            $current = array_shift($stack);
            $descendants[] = $current;
            foreach ($current->getChildren() as $child) {
                $stack[] = $child;
            }
        }

        return $descendants;
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<DocumentCategoryInterface> */
class DocumentCategoryRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentCategory::class, DocumentCategoryInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.name', Order::Ascending->value);
        $countQb = $this->createQueryBuilder('c')->select('COUNT(c.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return DocumentCategory[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')->orderBy('c.name', Order::Ascending->value)->getQuery()->getResult();
    }
}

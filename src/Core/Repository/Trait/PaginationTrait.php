<?php

declare(strict_types=1);

namespace Aurora\Core\Repository\Trait;

use Doctrine\ORM\QueryBuilder;

trait PaginationTrait
{
    private function paginate(
        QueryBuilder $queryBuilder,
        QueryBuilder $countQueryBuilder,
        int $page,
        int $limit,
    ): array {
        $total = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $items = $queryBuilder->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }
}

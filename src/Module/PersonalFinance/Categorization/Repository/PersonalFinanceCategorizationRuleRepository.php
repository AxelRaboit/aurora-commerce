<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRule;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceCategorizationRuleInterface> */
class PersonalFinanceCategorizationRuleRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceCategorizationRule::class, PersonalFinanceCategorizationRuleInterface::class);
    }

    public function findOneForUserByPattern(CoreUserInterface $user, string $pattern): ?PersonalFinanceCategorizationRuleInterface
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.pattern = :pattern')
            ->setParameter('user', $user)
            ->setParameter('pattern', $pattern)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<string> $patterns
     *
     * @return list<PersonalFinanceCategorizationRuleInterface>
     */
    public function findForUserByPatterns(CoreUserInterface $user, array $patterns): array
    {
        if ([] === $patterns) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->where('r.user = :user')
            ->andWhere('r.pattern IN (:patterns)')
            ->setParameter('user', $user)
            ->setParameter('patterns', $patterns)
            ->getQuery()
            ->getResult();
    }

    /**
     * Paginated list scoped to the user. Sorted by hits desc by default
     * (most-used rules at the top).
     *
     * @return array{items: list<PersonalFinanceCategorizationRuleInterface>, total: int, page: int, totalPages: int}
     */
    public function findPaginatedForUser(CoreUserInterface $user, int $page, int $limit = 30, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.hits', Order::Descending->value)
            ->addOrderBy('r.id', Order::Descending->value);

        $countQb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->setParameter('user', $user);

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(r.pattern) LIKE :search OR LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
            $countQb->leftJoin('r.category', 'c')->andWhere('LOWER(r.pattern) LIKE :search OR LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }
}

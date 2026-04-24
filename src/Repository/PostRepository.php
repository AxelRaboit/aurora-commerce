<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Enum\PostStatusEnum;
use App\Repository\Trait\PaginationTrait;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?int $postTypeId = null, string $locale = 'fr', bool $trashed = false): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 't', 'WITH', 't.locale = :locale')
            ->leftJoin('p.postType', 'pt')
            ->addSelect('t', 'pt')
            ->setParameter('locale', $locale)
            ->orderBy('p.createdAt', Order::Descending->value);

        $countQueryBuilder = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.translations', 't', 'WITH', 't.locale = :locale')
            ->setParameter('locale', $locale);

        $trashCondition = $trashed ? 'p.deletedAt IS NOT NULL' : 'p.deletedAt IS NULL';
        $queryBuilder->andWhere($trashCondition);
        $countQueryBuilder->andWhere($trashCondition);

        if ($search) {
            $condition = 'LOWER(t.title) LIKE :search';
            $param = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere($condition)->setParameter('search', $param);
            $countQueryBuilder->andWhere($condition)->setParameter('search', $param);
        }

        if (null !== $postTypeId) {
            $condition = 'p.postType = :postTypeId';
            $queryBuilder->andWhere($condition)->setParameter('postTypeId', $postTypeId);
            $countQueryBuilder->andWhere($condition)->setParameter('postTypeId', $postTypeId);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * @return list<Post>
     */
    public function findPurgeable(DateTimeImmutable $cutoff): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.deletedAt IS NOT NULL')
            ->andWhere('p.deletedAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Post>
     */
    public function findScheduledDueBy(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.scheduledAt IS NOT NULL')
            ->andWhere('p.scheduledAt <= :now')
            ->setParameter('status', PostStatusEnum::Scheduled)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function countTrashed(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Counts posts created since the given date, grouped by YYYY-MM.
     *
     * @return array<string, int> map of 'YYYY-MM' => count
     */
    public function countByMonthSince(DateTimeImmutable $since): array
    {
        $sqlQuery = <<<'SQL'
                SELECT TO_CHAR(created_at, 'YYYY-MM') AS month, COUNT(*) AS count
                FROM posts
                WHERE created_at >= :since
                GROUP BY month
                ORDER BY month ASC
            SQL;

        $rows = $this->getEntityManager()
            ->getConnection()
            ->fetchAllAssociative($sqlQuery, ['since' => $since->format('Y-m-d H:i:s')]);

        $monthCountMap = [];
        foreach ($rows as $row) {
            $monthCountMap[$row['month']] = (int) $row['count'];
        }

        return $monthCountMap;
    }

    /**
     * @return list<Post>
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.postType', 'pt')
            ->leftJoin('p.translations', 't')
            ->addSelect('pt', 't')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

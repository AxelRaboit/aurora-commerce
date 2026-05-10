<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<OcrJobInterface>
 */
class OcrJobRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OcrJob::class, OcrJobInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?OcrJobStatusEnum $status = null): array
    {
        $queryBuilder = $this->createQueryBuilder('j')->orderBy('j.createdAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('j')->select('COUNT(j.id)');

        if ($status instanceof OcrJobStatusEnum) {
            $queryBuilder->andWhere('j.status = :status')->setParameter('status', $status);
            $countQueryBuilder->andWhere('j.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * Jobs stuck in an in-progress status (Extracting or Parsing) whose
     * startedAt is older than the given threshold — typically due to a worker
     * crash mid-pipeline.
     *
     * @return list<OcrJobInterface>
     */
    public function findStuck(int $maxAgeMinutes = 60): array
    {
        $cutoff = new DateTimeImmutable(sprintf('-%d minutes', $maxAgeMinutes));

        return $this->createQueryBuilder('j')
            ->where('j.status IN (:statuses)')
            ->andWhere('j.startedAt < :cutoff OR (j.startedAt IS NULL AND j.createdAt < :cutoff)')
            ->setParameter('statuses', [OcrJobStatusEnum::Extracting, OcrJobStatusEnum::Parsing])
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }

    /**
     * Most recently created jobs, used to seed the import dashboard with the
     * latest activity at first paint.
     *
     * @return list<OcrJob>
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('j')
            ->addSelect('
                CASE j.status
                    WHEN :extracting THEN 0
                    WHEN :parsing    THEN 1
                    WHEN :queued     THEN 2
                    ELSE                  3
                END AS HIDDEN status_priority
            ')
            ->setParameter('extracting', OcrJobStatusEnum::Extracting)
            ->setParameter('parsing', OcrJobStatusEnum::Parsing)
            ->setParameter('queued', OcrJobStatusEnum::Queued)
            ->orderBy('status_priority', Order::Ascending->value)
            ->addOrderBy('j.createdAt', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

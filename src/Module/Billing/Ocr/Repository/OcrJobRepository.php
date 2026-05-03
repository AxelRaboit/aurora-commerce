<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OcrJob>
 */
class OcrJobRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OcrJob::class);
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

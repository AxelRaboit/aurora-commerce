<?php

declare(strict_types=1);

namespace App\Repository\Auth;

use App\Entity\AccessRequest;
use App\Enum\AccessRequestStatusEnum;
use App\Repository\Trait\PaginationTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessRequest>
 */
class AccessRequestRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessRequest::class);
    }

    public function findByToken(string $token): ?AccessRequest
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function deleteProcessed(): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->where('a.status IN (:statuses)')
            ->setParameter('statuses', [AccessRequestStatusEnum::Approved, AccessRequestStatusEnum::Rejected])
            ->getQuery()
            ->execute();
    }

    /**
     * @return array{items: AccessRequest[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedAdmin(int $page = 1, int $limit = 20): array
    {
        $queryBuilder = $this->createQueryBuilder('a')->orderBy('a.createdAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('a')->select('COUNT(a.id)');

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }
}

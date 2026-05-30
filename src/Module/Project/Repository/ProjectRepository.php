<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ProjectInterface> */
class ProjectRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class, ProjectInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?ProjectStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.responsibleUser', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', Order::Descending->value);

        $countQb = $this->createQueryBuilder('p')->select('COUNT(p.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(p.title) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(p.title) LIKE :search')->setParameter('search', $pattern);
        }

        if ($status instanceof ProjectStatusEnum) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
            $countQb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return list<Project> */
    public function searchByTitle(string $query, int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.title) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($query).'%')
            ->orderBy('p.createdAt', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Project> */
    public function findByStatus(ProjectStatusEnum $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Project> */
    public function findWithTasks(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->addSelect('t')
            ->orderBy('p.createdAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}

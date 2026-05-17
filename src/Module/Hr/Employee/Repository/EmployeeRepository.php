<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<EmployeeInterface> */
class EmployeeRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class, EmployeeInterface::class);
    }

    public function findOneByUser(CoreUserInterface $user): ?EmployeeInterface
    {
        return $this->findOneBy(['user' => $user]);
    }

    /** @return array{items: list<EmployeeInterface>, total: int, page: int, totalPages: int} */
    public function findPaginated(int $page, int $limit = 20, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('employee')
            ->leftJoin('employee.service', 'service')->addSelect('service')
            ->leftJoin('employee.agency', 'agency')->addSelect('agency')
            ->orderBy('employee.lastName', Order::Ascending->value)
            ->addOrderBy('employee.firstName', Order::Ascending->value);

        $countQb = $this->createQueryBuilder('employee')->select('COUNT(employee.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(employee.firstName) LIKE :search OR LOWER(employee.lastName) LIKE :search OR LOWER(employee.jobTitle) LIKE :search OR LOWER(service.name) LIKE :search OR LOWER(agency.name) LIKE :search')
                ->setParameter('search', $pattern);
            $countQb->leftJoin('employee.service', 'service2')
                ->leftJoin('employee.agency', 'agency2')
                ->andWhere('LOWER(employee.firstName) LIKE :search OR LOWER(employee.lastName) LIKE :search OR LOWER(employee.jobTitle) LIKE :search OR LOWER(service2.name) LIKE :search OR LOWER(agency2.name) LIKE :search')
                ->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return list<EmployeeInterface> */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('employee')
            ->orderBy('employee.lastName', Order::Ascending->value)
            ->addOrderBy('employee.firstName', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

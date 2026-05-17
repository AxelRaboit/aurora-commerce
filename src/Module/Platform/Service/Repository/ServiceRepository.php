<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Service\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Platform\Service\Entity\Service;
use Aurora\Module\Platform\Service\Entity\ServiceInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ServiceInterface>
 */
class ServiceRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class, ServiceInterface::class);
    }

    /** @return Service[] */
    public function findAllAlphabetical(): array
    {
        return $this->createQueryBuilder('service')
            ->orderBy('service.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

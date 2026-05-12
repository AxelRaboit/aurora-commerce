<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTag;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<DocumentTagInterface> */
class DocumentTagRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentTag::class, DocumentTagInterface::class);
    }

    /** @return list<DocumentTagInterface> */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

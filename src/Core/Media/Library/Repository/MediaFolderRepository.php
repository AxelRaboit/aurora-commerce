<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Repository;

use Aurora\Core\Media\Library\Entity\MediaFolder;
use Aurora\Core\Media\Library\Entity\MediaFolderInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<MediaFolderInterface>
 */
class MediaFolderRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaFolder::class, MediaFolderInterface::class);
    }

    /**
     * @return list<MediaFolder>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.position', Order::Ascending->value)
            ->addOrderBy('f.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}

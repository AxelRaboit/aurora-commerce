<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Media\Library\Entity\MediaFolder;
use Aurora\Module\Media\Library\Entity\MediaFolderInterface;
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

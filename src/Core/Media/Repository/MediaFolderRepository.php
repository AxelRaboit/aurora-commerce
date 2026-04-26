<?php

declare(strict_types=1);

namespace App\Core\Media\Repository;

use App\Core\Media\Entity\MediaFolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaFolder>
 */
class MediaFolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaFolder::class);
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

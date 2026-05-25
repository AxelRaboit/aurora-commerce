<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Media\Library\Entity\MediaVersion;
use Aurora\Module\Media\Library\Entity\MediaVersionInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<MediaVersionInterface> */
class MediaVersionRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaVersion::class, MediaVersionInterface::class);
    }

    /** @return list<MediaVersionInterface> */
    public function findByMedia(MediaInterface $media): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.media = :media')
            ->setParameter('media', $media)
            ->orderBy('v.versionNumber', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    public function getNextVersionNumber(MediaInterface $media): int
    {
        $max = $this->createQueryBuilder('v')
            ->select('MAX(v.versionNumber)')
            ->andWhere('v.media = :media')
            ->setParameter('media', $media)
            ->getQuery()
            ->getSingleScalarResult();

        return null !== $max ? (int) $max + 1 : 1;
    }
}

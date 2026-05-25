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

    /**
     * Versions beyond the most recent $limit (oldest first to delete), so the
     * caller can drop their rows and physical files. Empty when limit <= 0.
     *
     * @return list<MediaVersionInterface>
     */
    public function findPrunable(MediaInterface $media, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $this->createQueryBuilder('v')
            ->andWhere('v.media = :media')
            ->setParameter('media', $media)
            ->orderBy('v.versionNumber', Order::Descending->value)
            ->setFirstResult($limit)
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

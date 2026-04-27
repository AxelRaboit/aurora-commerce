<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Repository;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function getTotalStorageSize(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COALESCE(SUM(m.size), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Media>
     */
    public function searchByName(string $query, int $limit = 10): array
    {
        $like = '%'.mb_strtolower($query).'%';

        return $this->createQueryBuilder('m')
            ->where('LOWER(m.originalName) LIKE :search')
            ->orWhere('LOWER(m.alt) LIKE :search')
            ->setParameter('search', $like)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, int> map of folder_id => media count
     */
    public function countGroupedByFolders(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.folder) AS folderId, COUNT(m.id) AS cnt')
            ->where('m.folder IS NOT NULL')
            ->groupBy('m.folder')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['folderId']] = (int) $row['cnt'];
        }

        return $map;
    }

    /**
     * @return list<Media>
     */
    public function findByFolder(?MediaFolder $folder, ?string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', Order::Descending->value);

        if (!$folder instanceof MediaFolder) {
            $queryBuilder->andWhere('m.folder IS NULL');
        } else {
            $queryBuilder->andWhere('m.folder = :folder')->setParameter('folder', $folder);
        }

        if (null !== $search && '' !== $search) {
            $queryBuilder
                ->andWhere('LOWER(m.originalName) LIKE :search OR LOWER(m.alt) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

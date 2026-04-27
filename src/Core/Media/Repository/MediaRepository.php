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
     * Count all places a given media is directly referenced:
     * - as a featured image on a post
     * - inside EditorJS JSON blocks of post translations
     *
     * @return array{directCount: int, contentCount: int, total: int}
     */
    public function countUsages(int $mediaId): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $directCount = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM posts WHERE featured_media_id = :id',
            ['id' => $mediaId],
        );

        $contentCount = (int) $connection->fetchOne(
            'SELECT COUNT(DISTINCT post_id) FROM post_translations WHERE blocks::text LIKE :pattern',
            ['pattern' => '%"mediaId":'.$mediaId.'%'],
        );

        return [
            'directCount' => $directCount,
            'contentCount' => $contentCount,
            'total' => $directCount + $contentCount,
        ];
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
            ->leftJoin('m.folder', 'f')
            ->addSelect('f')
            ->orderBy('m.position', Order::Ascending->value)
            ->addOrderBy('m.createdAt', Order::Descending->value);

        // Cross-folder search: ignore folder filter when a search term is provided
        if (null !== $search && '' !== $search) {
            $queryBuilder
                ->andWhere('LOWER(m.originalName) LIKE :search OR LOWER(m.alt) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        } elseif (!$folder instanceof MediaFolder) {
            $queryBuilder->andWhere('m.folder IS NULL');
        } else {
            $queryBuilder->andWhere('m.folder = :folder')->setParameter('folder', $folder);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

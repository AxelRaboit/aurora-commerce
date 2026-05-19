<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Notes\Block\Entity\BlockNote;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<BlockNoteInterface> */
class BlockNoteRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlockNote::class, BlockNoteInterface::class);
    }

    /**
     * Flat list of all block notes for a user — metadata only, no blocks.
     * Front rebuilds the tree from parent_id + position.
     *
     * @return list<array<string, mixed>>
     */
    public function findFlatListForUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('n')
            ->select('n.id', 'n.title', 'n.tags', 'n.position', 'n.createdAt', 'n.updatedAt', 'IDENTITY(n.parent) AS parentId')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.position', Order::Ascending->value)
            ->addOrderBy('n.createdAt', Order::Descending->value)
            ->getQuery()
            ->getArrayResult();
    }

    public function findOneByUserAndId(CoreUserInterface $user, int $id): ?BlockNoteInterface
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Tag histogram across the user's block notes. Same shape as
     * MarkdownNoteRepository — kept independent so each module owns its
     * vocabulary.
     *
     * @return array<string, int>
     */
    public function findTagCountsForUser(CoreUserInterface $user): array
    {
        $rows = $this->createQueryBuilder('n')
            ->select('n.tags')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $tags = $row['tags'] ?? [];
            if (!is_array($tags)) {
                continue;
            }

            foreach ($tags as $tag) {
                if (!is_string($tag)) {
                    continue;
                }

                $trimmed = mb_trim($tag);
                if ('' === $trimmed) {
                    continue;
                }

                $counts[$trimmed] = ($counts[$trimmed] ?? 0) + 1;
            }
        }

        ksort($counts, SORT_NATURAL | SORT_FLAG_CASE);

        return $counts;
    }

    public function findMaxPositionForUserAndParent(CoreUserInterface $user, ?int $parentId): ?int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('MAX(n.position)')
            ->where('n.user = :user')
            ->setParameter('user', $user);

        if (null === $parentId) {
            $qb->andWhere('n.parent IS NULL');
        } else {
            $qb->andWhere('IDENTITY(n.parent) = :parentId')
                ->setParameter('parentId', $parentId);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return null === $result ? null : (int) $result;
    }

    /**
     * Lightweight full-text search against block payloads (decrypted in
     * PHP). Returns the user's notes whose title OR any block's textual
     * payload matches $query. Acceptable for ≤ a few hundred notes per
     * user — switch to a search index if the volume grows.
     *
     * @return list<BlockNoteInterface>
     */
    public function findAllForUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}

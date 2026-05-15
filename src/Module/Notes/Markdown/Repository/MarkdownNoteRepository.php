<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<MarkdownNoteInterface> */
class MarkdownNoteRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarkdownNote::class, MarkdownNoteInterface::class);
    }

    /**
     * Flat list of all notes for a user, without content (loaded on demand).
     * Front rebuilds the tree from parent_id + position.
     *
     * @return list<MarkdownNoteInterface>
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

    /**
     * Full notes (with content) for a user — used by graph/backlinks/unlinked
     * mentions. Loads everything into memory; monitor on large volumes.
     *
     * @return list<MarkdownNoteInterface>
     */
    public function findAllWithContentForUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndId(CoreUserInterface $user, int $id): ?MarkdownNoteInterface
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
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
}

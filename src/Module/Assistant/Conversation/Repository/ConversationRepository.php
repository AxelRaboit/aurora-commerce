<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Assistant\Conversation\Entity\Conversation;
use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;
use Aurora\Module\Assistant\Conversation\Entity\Message;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ConversationInterface> */
class ConversationRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class, ConversationInterface::class);
    }

    /**
     * Per-user listing for the sidebar, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function findListForUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.title', 'c.model', 'c.createdAt', 'c.updatedAt')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', Order::Descending->value)
            ->getQuery()
            ->getArrayResult();
    }

    public function findOneByUserAndId(CoreUserInterface $user, int $id): ?ConversationInterface
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findMaxPositionFor(ConversationInterface $conversation): ?int
    {
        $result = $this->getEntityManager()->createQuery(
            'SELECT MAX(m.position) FROM '.Message::class.' m WHERE m.conversation = :conversation',
        )->setParameter('conversation', $conversation)->getSingleScalarResult();

        return null === $result ? null : (int) $result;
    }
}

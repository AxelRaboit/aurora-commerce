<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Assistant\Conversation\Entity\Message;
use Aurora\Module\Assistant\Conversation\Entity\MessageInterface;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<MessageInterface> */
class MessageRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class, MessageInterface::class);
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Entity;

use Aurora\Module\Assistant\Conversation\Repository\ConversationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'core_assistant_conversations')]
#[ORM\Index(name: 'idx_assistant_conversations_user', columns: ['user_id'])]
class Conversation extends AbstractConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_assistant_conversation_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

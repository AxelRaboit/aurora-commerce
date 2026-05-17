<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Entity;

use Aurora\Module\Assistant\Conversation\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'core_assistant_messages')]
#[ORM\Index(name: 'idx_assistant_messages_conversation', columns: ['conversation_id'])]
class Message extends AbstractMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_assistant_message_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

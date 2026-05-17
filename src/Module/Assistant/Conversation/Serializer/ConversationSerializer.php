<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Serializer;

use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;
use Aurora\Module\Assistant\Conversation\Entity\MessageInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ConversationSerializerInterface::class)]
class ConversationSerializer implements ConversationSerializerInterface
{
    public function serializeListItem(ConversationInterface $conversation): array
    {
        return [
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'model' => $conversation->getModel(),
            'createdAt' => $conversation->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $conversation->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public function serializeDetail(ConversationInterface $conversation): array
    {
        return [
            ...$this->serializeListItem($conversation),
            'messages' => array_map($this->serializeMessage(...), $conversation->getMessages()->toArray()),
        ];
    }

    public function serializeMessage(MessageInterface $message): array
    {
        return [
            'id' => $message->getId(),
            'role' => $message->getRole()->value,
            'content' => $message->getContent(),
            'toolCalls' => $message->getToolCalls(),
            'toolCallId' => $message->getToolCallId(),
            'toolName' => $message->getToolName(),
            'position' => $message->getPosition(),
            'awaitingConfirmation' => $message->isAwaitingConfirmation(),
            'createdAt' => $message->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}

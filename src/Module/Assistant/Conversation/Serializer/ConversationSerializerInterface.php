<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Serializer;

use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;
use Aurora\Module\Assistant\Conversation\Entity\MessageInterface;

interface ConversationSerializerInterface
{
    /** @return array<string, mixed> Lightweight projection — no messages, used by the sidebar list. */
    public function serializeListItem(ConversationInterface $conversation): array;

    /** @return array<string, mixed> Full projection — includes the ordered message list. */
    public function serializeDetail(ConversationInterface $conversation): array;

    /** @return array<string, mixed> */
    public function serializeMessage(MessageInterface $message): array;
}

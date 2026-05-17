<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Manager;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\Conversation\Dto\MessageInputInterface;
use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;

interface ConversationManagerInterface
{
    public function create(CoreUserInterface $user): ConversationInterface;

    public function delete(ConversationInterface $conversation): void;

    /**
     * Append a user message to the conversation, run the LLM (with tool
     * roundtrips if needed), and return the conversation with all new
     * messages persisted.
     */
    public function sendMessage(ConversationInterface $conversation, MessageInputInterface $input): ConversationInterface;
}

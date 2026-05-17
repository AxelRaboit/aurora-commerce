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

    /**
     * Resume a conversation that paused on a pending tool confirmation.
     * `$decisions` maps each tool_call_id → "approve" or "reject". Calls
     * absent from the map default to "reject".
     *
     * @param array<string, string> $decisions
     */
    public function resumeAfterConfirmation(ConversationInterface $conversation, array $decisions): ConversationInterface;
}

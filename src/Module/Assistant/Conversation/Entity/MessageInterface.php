<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Assistant\Conversation\Enum\MessageRoleEnum;

interface MessageInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getConversation(): ConversationInterface;

    public function setConversation(ConversationInterface $conversation): static;

    public function getRole(): MessageRoleEnum;

    public function setRole(MessageRoleEnum $role): static;

    public function getContent(): string;

    public function setContent(string $content): static;

    /** @return list<array<string, mixed>>|null */
    public function getToolCalls(): ?array;

    /** @param list<array<string, mixed>>|null $toolCalls */
    public function setToolCalls(?array $toolCalls): static;

    public function getToolCallId(): ?string;

    public function setToolCallId(?string $toolCallId): static;

    public function getToolName(): ?string;

    public function setToolName(?string $toolName): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;
}

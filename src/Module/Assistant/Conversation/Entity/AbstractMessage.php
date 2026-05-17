<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Entity;

use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Assistant\Conversation\Enum\MessageRoleEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single turn in a {@see ConversationInterface}. Stores the role, the
 * textual content (encrypted at rest — chat payloads can contain
 * sensitive paths/identifiers), and the optional tool-call metadata.
 *
 * Two tool-related fields :
 *  - `toolCalls` (JSON) is set on `assistant` messages when the model
 *    decides to invoke one or more tools. Shape matches the Ollama /
 *    OpenAI `message.tool_calls[]` array.
 *  - `toolCallId` + `toolName` are set on `tool` messages — they identify
 *    which call the result corresponds to (matches the model's id),
 *    enabling correct correlation when multiple parallel calls happen.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMessage implements MessageInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: ConversationInterface::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ConversationInterface $conversation;

    #[ORM\Column(length: 20, enumType: MessageRoleEnum::class)]
    protected MessageRoleEnum $role = MessageRoleEnum::User;

    #[ORM\Column(type: EncryptedTextType::NAME)]
    protected string $content = '';

    /** @var list<array<string, mixed>>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $toolCalls = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $toolCallId = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $toolName = null;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    protected int $position = 0;

    /**
     * Set on assistant messages whose `toolCalls` contain at least one
     * mutating tool: the chat loop persists the message, then halts so
     * the UI can render a confirmation prompt. Flipped to false once the
     * user approves or rejects each call.
     */
    #[ORM\Column(options: ['default' => false])]
    protected bool $awaitingConfirmation = false;

    public function getConversation(): ConversationInterface
    {
        return $this->conversation;
    }

    public function setConversation(ConversationInterface $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getRole(): MessageRoleEnum
    {
        return $this->role;
    }

    public function setRole(MessageRoleEnum $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getToolCalls(): ?array
    {
        return $this->toolCalls;
    }

    public function setToolCalls(?array $toolCalls): static
    {
        $this->toolCalls = $toolCalls;

        return $this;
    }

    public function getToolCallId(): ?string
    {
        return $this->toolCallId;
    }

    public function setToolCallId(?string $toolCallId): static
    {
        $this->toolCallId = $toolCallId;

        return $this;
    }

    public function getToolName(): ?string
    {
        return $this->toolName;
    }

    public function setToolName(?string $toolName): static
    {
        $this->toolName = $toolName;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->awaitingConfirmation;
    }

    public function setAwaitingConfirmation(bool $awaitingConfirmation): static
    {
        $this->awaitingConfirmation = $awaitingConfirmation;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Entity;

use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Core\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A chat conversation between a user and the local LLM. Holds the
 * ordered list of `Message` rows (system/user/assistant/tool exchanges).
 * Title is encrypted at rest like `MarkdownNote` — chats can carry
 * sensitive content (paths, internal entity names, etc.).
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractConversation implements ConversationInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    /**
     * Snapshot of the user's agency at creation time — enables future
     * agency-wide analytics without schema changes. Nullable: users may
     * not be attached to any agency.
     */
    #[ORM\ManyToOne(targetEntity: AgencyInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?AgencyInterface $agency = null;

    #[ORM\Column(type: EncryptedTextType::NAME, nullable: true)]
    protected ?string $title = null;

    /** Model used for the conversation — kept on the row so a later model swap doesn't break old threads. */
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $model = null;

    /** @var Collection<int, MessageInterface> */
    #[ORM\OneToMany(targetEntity: MessageInterface::class, mappedBy: 'conversation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getUser(): CoreUserInterface
    {
        return $this->user;
    }

    public function setUser(CoreUserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAgency(): ?AgencyInterface
    {
        return $this->agency;
    }

    public function setAgency(?AgencyInterface $agency): static
    {
        $this->agency = $agency;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(MessageInterface $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Entity;

use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMarkdownNote implements MarkdownNoteInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected CoreUserInterface $user;

    /**
     * Snapshot of the user's agency at creation time. Enables future
     * agency-wide queries without schema changes. Nullable because users
     * may not be attached to any agency.
     */
    #[ORM\ManyToOne(targetEntity: AgencyInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?AgencyInterface $agency = null;

    #[ORM\ManyToOne(targetEntity: MarkdownNoteInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MarkdownNoteInterface $parent = null;

    /** @var Collection<int, MarkdownNoteInterface> */
    #[ORM\OneToMany(targetEntity: MarkdownNoteInterface::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $children;

    #[ORM\Column(type: EncryptedTextType::NAME, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: EncryptedTextType::NAME, nullable: true)]
    protected ?string $content = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    protected array $tags = [];

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    protected int $position = 0;

    public function __construct()
    {
        $this->children = new ArrayCollection();
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

    public function getParent(): ?MarkdownNoteInterface
    {
        return $this->parent;
    }

    public function setParent(?MarkdownNoteInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = array_values($tags);

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
}

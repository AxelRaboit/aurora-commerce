<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Entity;

use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Block list is stored as a JSON column rather than a separate
 * `block` table. Each entry is a `{type, data}` map; ordering is
 * the array order — no per-block id, no position field. This removes
 * a whole class of sync/reorder bugs in exchange for losing per-block
 * SQL queries (full-text search still scans textual `data` keys in PHP).
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractBlockNote implements BlockNoteInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
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

    #[ORM\ManyToOne(targetEntity: BlockNoteInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?BlockNoteInterface $parent = null;

    /** @var Collection<int, BlockNoteInterface> */
    #[ORM\OneToMany(targetEntity: BlockNoteInterface::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $children;

    /** @var list<array{id?: string, type: string, data: array<string, mixed>}> */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    protected array $blocks = [];

    #[ORM\Column(type: EncryptedTextType::NAME, nullable: true)]
    protected ?string $title = null;

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

    public function getParent(): ?BlockNoteInterface
    {
        return $this->parent;
    }

    public function setParent(?BlockNoteInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function setBlocks(array $blocks): static
    {
        $this->blocks = $blocks;

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

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;

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

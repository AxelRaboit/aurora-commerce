<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Entity;

use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPostItNote implements PostItNoteInterface
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

    #[ORM\Column(type: EncryptedTextType::NAME, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: EncryptedTextType::NAME, nullable: true)]
    protected ?string $content = null;

    /**
     * Hex color code (e.g. `#FFEB3B`). Defaults to a soft yellow — the
     * canonical post-it tint. The UI ships a palette; storing the value
     * lets clients add custom colors without DB changes.
     */
    #[ORM\Column(type: Types::STRING, length: 7, options: ['default' => '#FFEB3B'])]
    protected string $color = '#FFEB3B';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $positionX = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $positionY = 0;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getPositionX(): int
    {
        return $this->positionX;
    }

    public function setPositionX(int $positionX): static
    {
        $this->positionX = $positionX;

        return $this;
    }

    public function getPositionY(): int
    {
        return $this->positionY;
    }

    public function setPositionY(int $positionY): static
    {
        $this->positionY = $positionY;

        return $this;
    }
}

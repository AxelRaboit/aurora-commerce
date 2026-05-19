<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPlanning implements PlanningInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 150)]
    protected string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 7, options: ['default' => '#3b82f6'])]
    protected string $color = '#3b82f6';

    #[ORM\Column(length: 64, options: ['default' => 'Europe/Paris'])]
    protected string $timezone = 'Europe/Paris';

    #[ORM\Column(length: 20, enumType: PlanningVisibilityEnum::class, options: ['default' => 'private'])]
    protected PlanningVisibilityEnum $visibility = PlanningVisibilityEnum::Private_;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CoreUserInterface $owner = null;

    #[ORM\ManyToOne(targetEntity: AgencyInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?AgencyInterface $agency = null;

    /** @var Collection<int, PlanningEventInterface> */
    #[ORM\OneToMany(targetEntity: PlanningEventInterface::class, mappedBy: 'planning', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['startAt' => 'ASC'])]
    protected Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getVisibility(): PlanningVisibilityEnum
    {
        return $this->visibility;
    }

    public function setVisibility(PlanningVisibilityEnum $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getOwner(): ?CoreUserInterface
    {
        return $this->owner;
    }

    public function setOwner(?CoreUserInterface $owner): static
    {
        $this->owner = $owner;

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

    /** @return Collection<int, PlanningEventInterface> */
    public function getEvents(): Collection
    {
        return $this->events;
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPlanningEvent implements PlanningEventInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: PlanningInterface::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PlanningInterface $planning;

    #[ORM\Column(length: 255)]
    protected string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $location = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $startAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $endAt;

    #[ORM\Column(options: ['default' => false])]
    protected bool $allDay = false;

    #[ORM\Column(length: 20, enumType: PlanningEventStatusEnum::class, options: ['default' => 'confirmed'])]
    protected PlanningEventStatusEnum $status = PlanningEventStatusEnum::Confirmed;

    /**
     * Polymorphic source: identifies the upstream entity that triggered
     * this event (e.g. 'medical_visit' / 42). Filled when the event was
     * created via the cross-module sync mechanism.
     */
    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $sourceType = null;

    #[ORM\Column(nullable: true)]
    protected ?int $sourceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $sourceLabel = null;

    /** @var Collection<int, CoreUserInterface> */
    protected Collection $attendees;

    public function __construct()
    {
        $this->attendees = new ArrayCollection();
    }

    public function getPlanning(): PlanningInterface
    {
        return $this->planning;
    }

    public function setPlanning(PlanningInterface $planning): static
    {
        $this->planning = $planning;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getStartAt(): DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function getStatus(): PlanningEventStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PlanningEventStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): static
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setSourceId(?int $sourceId): static
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getSourceLabel(): ?string
    {
        return $this->sourceLabel;
    }

    public function setSourceLabel(?string $sourceLabel): static
    {
        $this->sourceLabel = $sourceLabel;

        return $this;
    }

    /** @return Collection<int, CoreUserInterface> */
    public function getAttendees(): Collection
    {
        return $this->attendees;
    }

    public function addAttendee(CoreUserInterface $attendee): static
    {
        if (!$this->attendees->contains($attendee)) {
            $this->attendees->add($attendee);
        }

        return $this;
    }

    public function removeAttendee(CoreUserInterface $attendee): static
    {
        $this->attendees->removeElement($attendee);

        return $this;
    }
}

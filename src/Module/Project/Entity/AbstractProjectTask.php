<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractProjectTask implements ProjectTaskInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: ProjectInterface::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ProjectInterface $project;

    #[ORM\ManyToOne(targetEntity: ProjectColumnInterface::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: 'column_id', nullable: false, onDelete: 'CASCADE')]
    protected ProjectColumnInterface $column;

    #[ORM\Column(length: 255)]
    protected string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 20, enumType: ProjectTaskPriorityEnum::class, options: ['default' => 'medium'])]
    protected ProjectTaskPriorityEnum $priority = ProjectTaskPriorityEnum::Medium;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CoreUserInterface $assignee = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $dueDate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\Column(nullable: true)]
    protected ?int $storyPoints = null;

    #[ORM\Column(nullable: true)]
    protected ?int $estimateMinutes = null;

    /** @var Collection<int, ProjectLabelInterface> */
    protected Collection $labels;

    /** @var Collection<int, ProjectTaskItemInterface> */
    #[ORM\OneToMany(targetEntity: ProjectTaskItemInterface::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $items;

    /** @var Collection<int, ProjectTaskTimeEntryInterface> */
    #[ORM\OneToMany(targetEntity: ProjectTaskTimeEntryInterface::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['loggedAt' => 'DESC'])]
    protected Collection $timeEntries;

    /** @var Collection<int, ProjectTaskCommentInterface> */
    #[ORM\OneToMany(targetEntity: ProjectTaskCommentInterface::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    protected Collection $comments;

    /** @var Collection<int, MediaInterface> */
    protected Collection $attachments;

    /** @var Collection<int, CoreUserInterface> */
    protected Collection $watchers;

    #[ORM\ManyToOne(targetEntity: ProjectSprintInterface::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?ProjectSprintInterface $sprint = null;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->timeEntries = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->watchers = new ArrayCollection();
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getProject(): ProjectInterface
    {
        return $this->project;
    }

    public function setProject(ProjectInterface $project): static
    {
        $this->project = $project;

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

    public function getColumn(): ProjectColumnInterface
    {
        return $this->column;
    }

    public function setColumn(ProjectColumnInterface $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getPriority(): ProjectTaskPriorityEnum
    {
        return $this->priority;
    }

    public function setPriority(ProjectTaskPriorityEnum $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getAssignee(): ?CoreUserInterface
    {
        return $this->assignee;
    }

    public function setAssignee(?CoreUserInterface $assignee): static
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

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

    public function getStoryPoints(): ?int
    {
        return $this->storyPoints;
    }

    public function setStoryPoints(?int $storyPoints): static
    {
        $this->storyPoints = $storyPoints;

        return $this;
    }

    public function getEstimateMinutes(): ?int
    {
        return $this->estimateMinutes;
    }

    public function setEstimateMinutes(?int $estimateMinutes): static
    {
        $this->estimateMinutes = $estimateMinutes;

        return $this;
    }

    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(ProjectLabelInterface $label): static
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    public function removeLabel(ProjectLabelInterface $label): static
    {
        $this->labels->removeElement($label);

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getTimeEntries(): Collection
    {
        return $this->timeEntries;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(MediaInterface $media): static
    {
        if (!$this->attachments->contains($media)) {
            $this->attachments->add($media);
        }

        return $this;
    }

    public function removeAttachment(MediaInterface $media): static
    {
        $this->attachments->removeElement($media);

        return $this;
    }

    public function getWatchers(): Collection
    {
        return $this->watchers;
    }

    public function addWatcher(CoreUserInterface $user): static
    {
        if (!$this->watchers->contains($user)) {
            $this->watchers->add($user);
        }

        return $this;
    }

    public function removeWatcher(CoreUserInterface $user): static
    {
        $this->watchers->removeElement($user);

        return $this;
    }

    public function getSprint(): ?ProjectSprintInterface
    {
        return $this->sprint;
    }

    public function setSprint(?ProjectSprintInterface $sprint): static
    {
        $this->sprint = $sprint;

        return $this;
    }
}

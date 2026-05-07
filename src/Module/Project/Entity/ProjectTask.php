<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTaskRepository::class)]
#[ORM\Table(name: 'core_project_tasks')]
#[ORM\HasLifecycleCallbacks]
class ProjectTask
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_project_task_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: ProjectColumn::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: 'column_id', nullable: false, onDelete: 'CASCADE')]
    private ProjectColumn $column;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: ProjectTaskPriorityEnum::class, options: ['default' => 'medium'])]
    private ProjectTaskPriorityEnum $priority = ProjectTaskPriorityEnum::Medium;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignee = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $dueDate = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(nullable: true)]
    private ?int $storyPoints = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimateMinutes = null;

    /** @var Collection<int, ProjectLabel> */
    #[ORM\ManyToMany(targetEntity: ProjectLabel::class)]
    #[ORM\JoinTable(name: 'core_project_task_labels')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'label_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $labels;

    /** @var Collection<int, ProjectTaskItem> */
    #[ORM\OneToMany(targetEntity: ProjectTaskItem::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    /** @var Collection<int, ProjectTaskTimeEntry> */
    #[ORM\OneToMany(targetEntity: ProjectTaskTimeEntry::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['loggedAt' => 'DESC'])]
    private Collection $timeEntries;

    /** @var Collection<int, ProjectTaskComment> */
    #[ORM\OneToMany(targetEntity: ProjectTaskComment::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $comments;

    /** @var Collection<int, Media> */
    #[ORM\ManyToMany(targetEntity: Media::class)]
    #[ORM\JoinTable(name: 'core_project_task_attachments')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'media_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $attachments;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'core_project_task_watchers')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $watchers;

    #[ORM\ManyToOne(targetEntity: ProjectSprint::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProjectSprint $sprint = null;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->timeEntries = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->watchers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
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

    public function getColumn(): ProjectColumn
    {
        return $this->column;
    }

    public function setColumn(ProjectColumn $column): static
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

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
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

    /** @return Collection<int, ProjectLabel> */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(ProjectLabel $label): static
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    public function removeLabel(ProjectLabel $label): static
    {
        $this->labels->removeElement($label);

        return $this;
    }

    /** @return Collection<int, ProjectTaskItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /** @return Collection<int, ProjectTaskTimeEntry> */
    public function getTimeEntries(): Collection
    {
        return $this->timeEntries;
    }

    /** @return Collection<int, ProjectTaskComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, Media> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(Media $media): static
    {
        if (!$this->attachments->contains($media)) {
            $this->attachments->add($media);
        }

        return $this;
    }

    public function removeAttachment(Media $media): static
    {
        $this->attachments->removeElement($media);

        return $this;
    }

    /** @return Collection<int, User> */
    public function getWatchers(): Collection
    {
        return $this->watchers;
    }

    public function addWatcher(User $user): static
    {
        if (!$this->watchers->contains($user)) {
            $this->watchers->add($user);
        }

        return $this;
    }

    public function removeWatcher(User $user): static
    {
        $this->watchers->removeElement($user);

        return $this;
    }

    public function getSprint(): ?ProjectSprint
    {
        return $this->sprint;
    }

    public function setSprint(?ProjectSprint $sprint): static
    {
        $this->sprint = $sprint;

        return $this;
    }
}

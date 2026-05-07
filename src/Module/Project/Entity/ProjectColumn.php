<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectColumnRepository::class)]
#[ORM\Table(name: 'core_project_columns')]
#[ORM\HasLifecycleCallbacks]
class ProjectColumn
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_project_column_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'columns')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /** @var Collection<int, ProjectTask> */
    #[ORM\OneToMany(targetEntity: ProjectTask::class, mappedBy: 'column')]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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

    /** @return Collection<int, ProjectTask> */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractProjectColumn implements ProjectColumnInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: ProjectInterface::class, inversedBy: 'columns')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ProjectInterface $project;

    #[ORM\Column(length: 100)]
    protected string $label;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    /** @var Collection<int, ProjectTaskInterface> */
    #[ORM\OneToMany(targetEntity: ProjectTaskInterface::class, mappedBy: 'column')]
    protected Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
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

    public function getTasks(): Collection
    {
        return $this->tasks;
    }
}

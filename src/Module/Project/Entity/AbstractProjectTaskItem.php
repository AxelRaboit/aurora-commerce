<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractProjectTaskItem implements ProjectTaskItemInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: ProjectTaskInterface::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ProjectTaskInterface $task;

    #[ORM\Column(length: 255)]
    protected string $label;

    #[ORM\Column(options: ['default' => false])]
    protected bool $done = false;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    public function getTask(): ProjectTaskInterface
    {
        return $this->task;
    }

    public function setTask(ProjectTaskInterface $task): static
    {
        $this->task = $task;

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

    public function isDone(): bool
    {
        return $this->done;
    }

    public function setDone(bool $done): static
    {
        $this->done = $done;

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

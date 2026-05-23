<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWeldingWorkflowStepTask implements WeldingWorkflowStepTaskInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: WeldingWorkflowStepInterface::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?WeldingWorkflowStepInterface $workflowStep = null;

    /**
     * Pointer to the template task this instance was snapshotted from. Kept
     * nullable + SET NULL so that admins editing the template after a workflow
     * has started don't break running workflows.
     */
    #[ORM\ManyToOne(targetEntity: WeldingWorkflowStepTaskTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?WeldingWorkflowStepTaskTemplateInterface $taskTemplate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    /**
     * Snapshotted label at start time — keeps a stable trail even if the
     * template is edited later.
     */
    #[ORM\Column(length: 300)]
    protected string $label;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(options: ['default' => true])]
    protected bool $required = true;

    #[ORM\Column(options: ['default' => false])]
    protected bool $done = false;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CoreUserInterface $doneBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeInterface $doneAt = null;

    public function getWorkflowStep(): ?WeldingWorkflowStepInterface
    {
        return $this->workflowStep;
    }

    public function setWorkflowStep(?WeldingWorkflowStepInterface $workflowStep): static
    {
        $this->workflowStep = $workflowStep;

        return $this;
    }

    public function getTaskTemplate(): ?WeldingWorkflowStepTaskTemplateInterface
    {
        return $this->taskTemplate;
    }

    public function setTaskTemplate(?WeldingWorkflowStepTaskTemplateInterface $taskTemplate): static
    {
        $this->taskTemplate = $taskTemplate;

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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;

        return $this;
    }

    public function getDone(): bool
    {
        return $this->done;
    }

    public function setDone(bool $done): static
    {
        $this->done = $done;

        return $this;
    }

    public function getDoneBy(): ?CoreUserInterface
    {
        return $this->doneBy;
    }

    public function setDoneBy(?CoreUserInterface $doneBy): static
    {
        $this->doneBy = $doneBy;

        return $this;
    }

    public function getDoneAt(): ?DateTimeInterface
    {
        return $this->doneAt;
    }

    public function setDoneAt(?DateTimeInterface $doneAt): static
    {
        $this->doneAt = $doneAt;

        return $this;
    }
}

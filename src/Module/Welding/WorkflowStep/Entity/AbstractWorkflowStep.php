<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\Enum\WorkflowStepStatusEnum;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWorkflowStep implements WorkflowStepInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: WorkflowInterface::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?WorkflowInterface $workflow = null;

    #[ORM\ManyToOne(targetEntity: WorkflowStepTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    protected ?WorkflowStepTemplateInterface $stepTemplate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\Column(length: 30, enumType: WorkflowStepStatusEnum::class, options: ['default' => 'pending'])]
    protected WorkflowStepStatusEnum $status = WorkflowStepStatusEnum::Pending;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CoreUserInterface $completedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeInterface $completedAt = null;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CoreUserInterface $validatedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeInterface $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $validationComment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $rejectionComment = null;

    public function getWorkflow(): ?WorkflowInterface
    {
        return $this->workflow;
    }

    public function setWorkflow(?WorkflowInterface $workflow): static
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function getStepTemplate(): ?WorkflowStepTemplateInterface
    {
        return $this->stepTemplate;
    }

    public function setStepTemplate(?WorkflowStepTemplateInterface $stepTemplate): static
    {
        $this->stepTemplate = $stepTemplate;

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

    public function getStatus(): WorkflowStepStatusEnum
    {
        return $this->status;
    }

    public function setStatus(WorkflowStepStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCompletedBy(): ?CoreUserInterface
    {
        return $this->completedBy;
    }

    public function setCompletedBy(?CoreUserInterface $completedBy): static
    {
        $this->completedBy = $completedBy;

        return $this;
    }

    public function getCompletedAt(): ?DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getValidatedBy(): ?CoreUserInterface
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?CoreUserInterface $validatedBy): static
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }

    public function getValidatedAt(): ?DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?DateTimeInterface $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getValidationComment(): ?string
    {
        return $this->validationComment;
    }

    public function setValidationComment(?string $validationComment): static
    {
        $this->validationComment = $validationComment;

        return $this;
    }

    public function getRejectionComment(): ?string
    {
        return $this->rejectionComment;
    }

    public function setRejectionComment(?string $rejectionComment): static
    {
        $this->rejectionComment = $rejectionComment;

        return $this;
    }
}

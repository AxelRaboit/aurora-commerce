<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\Enum\WeldingWorkflowStepStatusEnum;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWeldingWorkflowStep implements WeldingWorkflowStepInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: WeldingWorkflowInterface::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?WeldingWorkflowInterface $workflow = null;

    #[ORM\ManyToOne(targetEntity: WeldingWorkflowStepTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    protected ?WeldingWorkflowStepTemplateInterface $stepTemplate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\Column(length: 30, enumType: WeldingWorkflowStepStatusEnum::class, options: ['default' => 'pending'])]
    protected WeldingWorkflowStepStatusEnum $status = WeldingWorkflowStepStatusEnum::Pending;

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

    public function getWorkflow(): ?WeldingWorkflowInterface
    {
        return $this->workflow;
    }

    public function setWorkflow(?WeldingWorkflowInterface $workflow): static
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function getStepTemplate(): ?WeldingWorkflowStepTemplateInterface
    {
        return $this->stepTemplate;
    }

    public function setStepTemplate(?WeldingWorkflowStepTemplateInterface $stepTemplate): static
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

    public function getStatus(): WeldingWorkflowStepStatusEnum
    {
        return $this->status;
    }

    public function setStatus(WeldingWorkflowStepStatusEnum $status): static
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

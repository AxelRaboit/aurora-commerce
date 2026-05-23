<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Welding\Enum\WeldingWorkflowStatusEnum;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWeldingWorkflow implements WeldingWorkflowInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: WeldingWorkflowTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    protected ?WeldingWorkflowTemplateInterface $template = null;

    #[ORM\ManyToOne(targetEntity: EmployeeInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?EmployeeInterface $assignee = null;

    #[ORM\Column(length: 30, enumType: WeldingWorkflowStatusEnum::class, options: ['default' => 'draft'])]
    protected WeldingWorkflowStatusEnum $status = WeldingWorkflowStatusEnum::Draft;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeInterface $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeInterface $rejectedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $rejectionReason = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $contextType = null;

    #[ORM\Column(nullable: true)]
    protected ?int $contextId = null;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTemplate(): ?WeldingWorkflowTemplateInterface
    {
        return $this->template;
    }

    public function setTemplate(?WeldingWorkflowTemplateInterface $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getAssignee(): ?EmployeeInterface
    {
        return $this->assignee;
    }

    public function setAssignee(?EmployeeInterface $assignee): static
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function getStatus(): WeldingWorkflowStatusEnum
    {
        return $this->status;
    }

    public function setStatus(WeldingWorkflowStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

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

    public function getRejectedAt(): ?DateTimeInterface
    {
        return $this->rejectedAt;
    }

    public function setRejectedAt(?DateTimeInterface $rejectedAt): static
    {
        $this->rejectedAt = $rejectedAt;

        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;

        return $this;
    }

    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    public function setContextType(?string $contextType): static
    {
        $this->contextType = $contextType;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setContextId(?int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }
}

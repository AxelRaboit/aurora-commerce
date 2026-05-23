<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWeldingWorkflowStepTaskTemplate implements WeldingWorkflowStepTaskTemplateInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: WeldingWorkflowStepTemplateInterface::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?WeldingWorkflowStepTemplateInterface $workflowStepTemplate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\Column(length: 300)]
    protected string $label;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(options: ['default' => true])]
    protected bool $required = true;

    public function getWorkflowStepTemplate(): ?WeldingWorkflowStepTemplateInterface
    {
        return $this->workflowStepTemplate;
    }

    public function setWorkflowStepTemplate(?WeldingWorkflowStepTemplateInterface $workflowStepTemplate): static
    {
        $this->workflowStepTemplate = $workflowStepTemplate;

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
}

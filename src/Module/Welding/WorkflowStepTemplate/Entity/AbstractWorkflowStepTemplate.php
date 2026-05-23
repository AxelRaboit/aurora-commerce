<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWorkflowStepTemplate implements WorkflowStepTemplateInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: WorkflowTemplateInterface::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?WorkflowTemplateInterface $workflowTemplate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\Column(length: 200)]
    protected string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    protected bool $requiresValidation = false;

    #[ORM\Column(length: 30, enumType: WeldingValidatorRoleEnum::class, nullable: true)]
    protected ?WeldingValidatorRoleEnum $validatorRole = null;

    public function getWorkflowTemplate(): ?WorkflowTemplateInterface
    {
        return $this->workflowTemplate;
    }

    public function setWorkflowTemplate(?WorkflowTemplateInterface $workflowTemplate): static
    {
        $this->workflowTemplate = $workflowTemplate;

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

    public function getRequiresValidation(): bool
    {
        return $this->requiresValidation;
    }

    public function setRequiresValidation(bool $requiresValidation): static
    {
        $this->requiresValidation = $requiresValidation;

        return $this;
    }

    public function getValidatorRole(): ?WeldingValidatorRoleEnum
    {
        return $this->validatorRole;
    }

    public function setValidatorRole(?WeldingValidatorRoleEnum $validatorRole): static
    {
        $this->validatorRole = $validatorRole;

        return $this;
    }
}

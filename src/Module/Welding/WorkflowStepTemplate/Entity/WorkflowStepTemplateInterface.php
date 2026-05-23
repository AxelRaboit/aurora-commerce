<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;
use Doctrine\Common\Collections\Collection;

interface WorkflowStepTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflowTemplate(): ?WorkflowTemplateInterface;

    public function setWorkflowTemplate(?WorkflowTemplateInterface $workflowTemplate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getRequiresValidation(): bool;

    public function setRequiresValidation(bool $requiresValidation): static;

    public function getValidatorRole(): ?WeldingValidatorRoleEnum;

    public function setValidatorRole(?WeldingValidatorRoleEnum $validatorRole): static;

    /** @return Collection<int, \Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface> */
    public function getPdfTemplates(): Collection;
}

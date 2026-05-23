<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use Doctrine\Common\Collections\Collection;

interface WeldingWorkflowStepTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflowTemplate(): ?WeldingWorkflowTemplateInterface;

    public function setWorkflowTemplate(?WeldingWorkflowTemplateInterface $workflowTemplate): static;

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

    /** @return Collection<int, \Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplateInterface> */
    public function getPdfTemplates(): Collection;
}

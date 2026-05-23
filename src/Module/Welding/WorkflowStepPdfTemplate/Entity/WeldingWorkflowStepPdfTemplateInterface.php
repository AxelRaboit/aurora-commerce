<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;

interface WeldingWorkflowStepPdfTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflowStepTemplate(): ?WeldingWorkflowStepTemplateInterface;

    public function setWorkflowStepTemplate(?WeldingWorkflowStepTemplateInterface $workflowStepTemplate): static;

    public function getPdfTemplate(): ?WeldingPdfTemplateInterface;

    public function setPdfTemplate(?WeldingPdfTemplateInterface $pdfTemplate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getRequired(): bool;

    public function setRequired(bool $required): static;
}

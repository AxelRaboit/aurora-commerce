<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;

interface WorkflowStepPdfTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getWorkflowStepTemplate(): ?WorkflowStepTemplateInterface;

    public function setWorkflowStepTemplate(?WorkflowStepTemplateInterface $workflowStepTemplate): static;

    public function getPdfTemplate(): ?PdfTemplateInterface;

    public function setPdfTemplate(?PdfTemplateInterface $pdfTemplate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getRequired(): bool;

    public function setRequired(bool $required): static;
}

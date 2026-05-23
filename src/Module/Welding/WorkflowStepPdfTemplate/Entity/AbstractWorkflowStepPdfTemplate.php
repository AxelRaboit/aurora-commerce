<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWorkflowStepPdfTemplate implements WorkflowStepPdfTemplateInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: WorkflowStepTemplateInterface::class, inversedBy: 'pdfTemplates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?WorkflowStepTemplateInterface $workflowStepTemplate = null;

    #[ORM\ManyToOne(targetEntity: PdfTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    protected ?PdfTemplateInterface $pdfTemplate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    protected bool $required = true;

    public function getWorkflowStepTemplate(): ?WorkflowStepTemplateInterface
    {
        return $this->workflowStepTemplate;
    }

    public function setWorkflowStepTemplate(?WorkflowStepTemplateInterface $workflowStepTemplate): static
    {
        $this->workflowStepTemplate = $workflowStepTemplate;

        return $this;
    }

    public function getPdfTemplate(): ?PdfTemplateInterface
    {
        return $this->pdfTemplate;
    }

    public function setPdfTemplate(?PdfTemplateInterface $pdfTemplate): static
    {
        $this->pdfTemplate = $pdfTemplate;

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

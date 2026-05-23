<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWeldingWorkflowStepPdfTemplate implements WeldingWorkflowStepPdfTemplateInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: WeldingWorkflowStepTemplateInterface::class, inversedBy: 'pdfTemplates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?WeldingWorkflowStepTemplateInterface $workflowStepTemplate = null;

    #[ORM\ManyToOne(targetEntity: WeldingPdfTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    protected ?WeldingPdfTemplateInterface $pdfTemplate = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

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

    public function getPdfTemplate(): ?WeldingPdfTemplateInterface
    {
        return $this->pdfTemplate;
    }

    public function setPdfTemplate(?WeldingPdfTemplateInterface $pdfTemplate): static
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

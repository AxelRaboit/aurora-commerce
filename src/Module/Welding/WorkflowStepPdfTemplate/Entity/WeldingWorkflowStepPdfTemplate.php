<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Repository\WeldingWorkflowStepPdfTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeldingWorkflowStepPdfTemplateRepository::class)]
#[ORM\Table(name: 'core_welding_workflow_step_pdf_templates')]
class WeldingWorkflowStepPdfTemplate extends AbstractWeldingWorkflowStepPdfTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_workflow_step_pdf_template_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

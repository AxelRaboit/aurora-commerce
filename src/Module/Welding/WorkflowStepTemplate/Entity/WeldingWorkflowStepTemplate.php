<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Entity;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeldingWorkflowStepTemplateRepository::class)]
#[ORM\Table(name: 'core_welding_workflow_step_templates')]
class WeldingWorkflowStepTemplate extends AbstractWeldingWorkflowStepTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_workflow_step_template_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, WeldingWorkflowStepPdfTemplateInterface> */
    #[ORM\OneToMany(mappedBy: 'workflowStepTemplate', targetEntity: WeldingWorkflowStepPdfTemplateInterface::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $pdfTemplates;

    public function __construct()
    {
        $this->pdfTemplates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPdfTemplates(): Collection
    {
        return $this->pdfTemplates;
    }
}

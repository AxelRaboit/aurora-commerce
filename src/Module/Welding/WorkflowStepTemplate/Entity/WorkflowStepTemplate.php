<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Entity;

use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WorkflowStepTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkflowStepTemplateRepository::class)]
#[ORM\Table(name: 'core_welding_workflow_step_templates')]
class WorkflowStepTemplate extends AbstractWorkflowStepTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_workflow_step_template_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, WorkflowStepPdfTemplateInterface> */
    #[ORM\OneToMany(mappedBy: 'workflowStepTemplate', targetEntity: WorkflowStepPdfTemplateInterface::class, cascade: ['persist'], orphanRemoval: true)]
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

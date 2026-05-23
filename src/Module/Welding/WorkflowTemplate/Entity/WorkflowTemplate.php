<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Entity;

use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkflowTemplateRepository::class)]
#[ORM\Table(name: 'core_welding_workflow_templates')]
class WorkflowTemplate extends AbstractWorkflowTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_workflow_template_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, WorkflowStepTemplateInterface> */
    #[ORM\OneToMany(mappedBy: 'workflowTemplate', targetEntity: WorkflowStepTemplateInterface::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $steps;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSteps(): Collection
    {
        return $this->steps;
    }
}

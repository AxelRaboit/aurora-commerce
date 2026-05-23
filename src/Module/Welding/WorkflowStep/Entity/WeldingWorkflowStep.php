<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Entity;

use Aurora\Module\Welding\WorkflowStep\Repository\WeldingWorkflowStepRepository;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeldingWorkflowStepRepository::class)]
#[ORM\Table(name: 'core_welding_workflow_steps')]
class WeldingWorkflowStep extends AbstractWeldingWorkflowStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_workflow_step_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, WeldingWorkflowStepTaskInterface> */
    #[ORM\OneToMany(targetEntity: WeldingWorkflowStepTaskInterface::class, mappedBy: 'workflowStep', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }
}

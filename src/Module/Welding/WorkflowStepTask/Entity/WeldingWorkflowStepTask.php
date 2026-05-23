<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Entity;

use Aurora\Module\Welding\WorkflowStepTask\Repository\WeldingWorkflowStepTaskRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeldingWorkflowStepTaskRepository::class)]
#[ORM\Table(name: 'core_welding_workflow_step_tasks')]
class WeldingWorkflowStepTask extends AbstractWeldingWorkflowStepTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_workflow_step_task_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

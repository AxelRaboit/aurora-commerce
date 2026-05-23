<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Entity;

use Aurora\Module\Welding\Workflow\Repository\WorkflowRepository;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkflowRepository::class)]
#[ORM\Table(name: 'core_welding_workflows')]
class Workflow extends AbstractWorkflow
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_workflow_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, WorkflowStepInterface> */
    #[ORM\OneToMany(mappedBy: 'workflow', targetEntity: WorkflowStepInterface::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
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

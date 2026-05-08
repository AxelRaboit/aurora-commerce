<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Module\Project\Repository\ProjectSprintRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectSprintRepository::class)]
#[ORM\Table(name: 'core_project_sprints')]
class ProjectSprint extends AbstractProjectSprint
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_core_project_sprint_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

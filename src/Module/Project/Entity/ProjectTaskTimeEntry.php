<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Module\Project\Repository\ProjectTaskTimeEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTaskTimeEntryRepository::class)]
#[ORM\Table(name: 'core_project_task_time_entries')]
class ProjectTaskTimeEntry extends AbstractProjectTaskTimeEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_project_task_time_entry_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

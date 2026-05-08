<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Module\Project\Repository\ProjectLabelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectLabelRepository::class)]
#[ORM\Table(name: 'core_project_labels')]
class ProjectLabel extends AbstractProjectLabel
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_core_project_label_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

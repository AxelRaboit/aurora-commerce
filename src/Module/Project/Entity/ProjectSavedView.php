<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Module\Project\Repository\ProjectSavedViewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectSavedViewRepository::class)]
#[ORM\Table(name: 'core_project_saved_views')]
class ProjectSavedView extends AbstractProjectSavedView
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_project_saved_view_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

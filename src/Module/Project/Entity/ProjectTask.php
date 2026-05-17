<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTaskRepository::class)]
#[ORM\Table(name: 'core_project_tasks')]
class ProjectTask extends AbstractProjectTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_core_project_task_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, ProjectLabelInterface> */
    #[ORM\ManyToMany(targetEntity: ProjectLabelInterface::class)]
    #[ORM\JoinTable(name: 'core_project_task_labels')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'label_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $labels;

    /** @var Collection<int, MediaInterface> */
    #[ORM\ManyToMany(targetEntity: MediaInterface::class)]
    #[ORM\JoinTable(name: 'core_project_task_attachments')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'media_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $attachments;

    /** @var Collection<int, CoreUserInterface> */
    #[ORM\ManyToMany(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinTable(name: 'core_project_task_watchers')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $watchers;

    public function getId(): ?int
    {
        return $this->id;
    }
}

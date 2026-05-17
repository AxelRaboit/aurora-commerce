<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Entity;

use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssistantMountPointRepository::class)]
#[ORM\Table(name: 'core_assistant_mount_points')]
#[ORM\Index(name: 'idx_assistant_mount_points_user', columns: ['user_id'])]
class AssistantMountPoint extends AbstractAssistantMountPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_assistant_mount_point_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

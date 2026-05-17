<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\MountPoint\Entity;

use Aurora\Core\Dev\MountPoint\Repository\MountPointRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MountPointRepository::class)]
#[ORM\Table(name: 'core_mount_points')]
class MountPoint extends AbstractMountPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_mount_point_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

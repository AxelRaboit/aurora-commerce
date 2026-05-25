<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Entity;

use Aurora\Module\Media\Library\Repository\MediaVersionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaVersionRepository::class)]
#[ORM\Table(name: 'core_media_versions')]
class MediaVersion extends AbstractMediaVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_media_version_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

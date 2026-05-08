<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Entity;

use Aurora\Core\Media\Repository\MediaFolderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaFolderRepository::class)]
#[ORM\Table(name: 'core_media_folders')]
#[ORM\Index(name: 'IDX_media_folders_parent', columns: ['parent_id'])]
class MediaFolder extends AbstractMediaFolder
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_media_folder_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

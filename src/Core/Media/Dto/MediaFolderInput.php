<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class MediaFolderInput implements MediaFolderInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'media.errors.folder_name_required')]
        #[Assert\Length(max: 150)]
        public readonly string $name,
        public readonly ?int $parentId = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }
}

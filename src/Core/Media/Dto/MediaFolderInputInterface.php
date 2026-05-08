<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Dto;

interface MediaFolderInputInterface
{
    public function getName(): string;

    public function getParentId(): ?int;
}

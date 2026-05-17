<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Dto;

interface MediaFolderInputInterface
{
    public function getName(): string;

    public function getParentId(): ?int;
}

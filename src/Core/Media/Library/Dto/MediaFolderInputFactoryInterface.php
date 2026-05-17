<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Dto;

interface MediaFolderInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MediaFolderInputInterface;
}

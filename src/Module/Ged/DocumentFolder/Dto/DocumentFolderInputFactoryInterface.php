<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Dto;

interface DocumentFolderInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): DocumentFolderInputInterface;
}

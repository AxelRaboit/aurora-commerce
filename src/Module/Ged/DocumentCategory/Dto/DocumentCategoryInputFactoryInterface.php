<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Dto;

interface DocumentCategoryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): DocumentCategoryInputInterface;
}

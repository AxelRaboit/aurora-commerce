<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Dto;

interface DocumentTagInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): DocumentTagInputInterface;
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Dto;

interface DocumentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): DocumentInputInterface;
}

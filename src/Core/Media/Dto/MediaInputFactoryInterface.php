<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Dto;

interface MediaInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MediaInputInterface;
}

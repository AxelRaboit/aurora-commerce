<?php

declare(strict_types=1);

namespace Aurora\Core\Service\Dto;

interface ServiceInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ServiceInputInterface;
}

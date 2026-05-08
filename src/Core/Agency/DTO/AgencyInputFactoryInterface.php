<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\DTO;

interface AgencyInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): AgencyInputInterface;
}

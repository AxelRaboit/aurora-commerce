<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Agency\Dto;

interface AgencyInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): AgencyInputInterface;
}

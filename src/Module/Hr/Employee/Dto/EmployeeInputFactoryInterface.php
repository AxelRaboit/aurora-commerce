<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Dto;

interface EmployeeInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): EmployeeInputInterface;
}

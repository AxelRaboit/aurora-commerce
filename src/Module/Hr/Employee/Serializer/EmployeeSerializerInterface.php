<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Serializer;

use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;

interface EmployeeSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(EmployeeInterface $employee): array;
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Manager;

use Aurora\Module\Hr\Employee\Dto\EmployeeInputInterface;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;

interface EmployeeManagerInterface
{
    public function create(EmployeeInputInterface $input): EmployeeInterface;

    public function update(EmployeeInterface $employee, EmployeeInputInterface $input): void;

    public function delete(EmployeeInterface $employee): void;
}

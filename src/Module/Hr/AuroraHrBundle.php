<?php

declare(strict_types=1);

namespace Aurora\Module\Hr;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;

/** Self-contained bundle for the Hr module. @see AbstractAuroraModuleBundle */
final class AuroraHrBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Hr';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            EmployeeInterface::class => Employee::class,
        ];
    }
}

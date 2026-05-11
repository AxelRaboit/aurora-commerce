<?php

declare(strict_types=1);

namespace Aurora\Module\Hr;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\ModuleToggleProviderInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Hr\Service\HrContext;

final readonly class HrModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private HrContext $hrContext) {}

    public function getId(): string
    {
        return 'hr';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('hr.employees.view'),
            new NavPermission('hr.employees.create'),
            new NavPermission('hr.employees.edit'),
            new NavPermission('hr.employees.delete'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->hrContext->isAdminEnabled()) {
            return [];
        }

        $items = [];

        if ($this->hrContext->isEmployeesEnabled()) {
            $items[] = new NavItem('backend_hr_employees', 'backend.nav.employees', 'users', requiredPrivilege: 'hr.employees.view', descriptionKey: 'backend.nav.employees_description');
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('hr', $items, priority: 45)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('hr', [
                new NavItem('backend_hr_employees', 'backend.nav.employees', 'users', requiredPrivilege: 'hr.employees.view', descriptionKey: 'backend.nav.employees_description'),
            ], priority: 45),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::HrEnabled->toToggle(),
            ModuleParameterEnum::HrEmployeesEnabled->toToggle(),
        ];
    }
}

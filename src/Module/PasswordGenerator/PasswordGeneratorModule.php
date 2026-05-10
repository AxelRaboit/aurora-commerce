<?php

declare(strict_types=1);

namespace Aurora\Module\PasswordGenerator;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavPermission;

final readonly class PasswordGeneratorModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'password_generator';
    }

    public function getPermissions(): array
    {
        return [new NavPermission('password_generator.use')];
    }

    public function getNavSections(): array
    {
        return [];
    }

    public function getCatalogNavSections(): array
    {
        return [];
    }
}

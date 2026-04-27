<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

interface ModuleInterface
{
    public function getId(): string;

    /** @return NavSection[] */
    public function getNavSections(): array;

    /** @return NavPermission[] */
    public function getPermissions(): array;
}

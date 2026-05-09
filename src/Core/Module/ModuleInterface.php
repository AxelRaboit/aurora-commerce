<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

interface ModuleInterface
{
    public function getId(): string;

    /** @return NavSection[] */
    public function getNavSections(): array;

    /** Returns nav sections regardless of whether the module is enabled (for catalog display). @return NavSection[] */
    public function getCatalogNavSections(): array;

    /** @return NavPermission[] */
    public function getPermissions(): array;
}

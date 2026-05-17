<?php

declare(strict_types=1);

namespace {{NAMESPACE}};

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;

final readonly class {{MODULE}}Module implements ModuleInterface
{
    public function getId(): string
    {
        return '{{MODULE_ID}}';
    }

    public function getPermissions(): array
    {
        return [new NavPermission('{{MODULE_ID}}.use')];
    }

    public function getNavSections(): array
    {
        return [
            new NavSection('{{MODULE_ID}}', [
                new NavItem(
                    'backend_{{MODULE_ID}}',
                    'backend.nav.{{MODULE_ID}}',
                    '{{ICON}}',
                    requiredPrivilege: '{{MODULE_ID}}.use',
                    descriptionKey: 'backend.nav.{{MODULE_ID}}_description',
                ),
            ], priority: {{PRIORITY}}),
        ];
    }

    public function getCatalogNavSections(): array
    {
        return $this->getNavSections();
    }
}

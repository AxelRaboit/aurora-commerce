<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;

final readonly class AssistantModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'assistant';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('assistant.use'),
        ];
    }

    public function getNavSections(): array
    {
        return [
            new NavSection('assistant', [
                new NavItem(
                    'backend_assistant_chat',
                    'backend.nav.assistant',
                    'message-square',
                    requiredPrivilege: 'assistant.use',
                    descriptionKey: 'backend.nav.assistant_description',
                ),
                new NavItem(
                    'backend_assistant_mount_points',
                    'backend.nav.assistant_mount_points',
                    'folder-key',
                    requiredPrivilege: 'assistant.use',
                    descriptionKey: 'backend.nav.assistant_mount_points_description',
                ),
            ], priority: 27),
        ];
    }

    public function getCatalogNavSections(): array
    {
        return $this->getNavSections();
    }
}

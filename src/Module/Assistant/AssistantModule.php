<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class AssistantModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private AssistantContext $assistantContext) {}

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
        if (!$this->assistantContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->assistantContext->isChatEnabled()) {
            $items[] = new NavItem(
                'backend_assistant_chat',
                'backend.nav.assistant',
                'message-square',
                requiredPrivilege: 'assistant.use',
                descriptionKey: 'backend.nav.assistant_description',
            );
        }

        if ($this->assistantContext->isMountPointsEnabled()) {
            $items[] = new NavItem(
                'backend_assistant_mount_points',
                'backend.nav.assistant_mount_points',
                'folder-key',
                requiredPrivilege: 'assistant.use',
                descriptionKey: 'backend.nav.assistant_mount_points_description',
            );
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('assistant', $items, priority: 27)];
    }

    public function getCatalogNavSections(): array
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

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::AssistantBackend->toToggle(),
            ModuleParameterEnum::AssistantChat->toToggle(),
            ModuleParameterEnum::AssistantMountPoints->toToggle(),
        ];
    }
}

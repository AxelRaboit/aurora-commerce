<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Assistant\Conversation\Entity\Conversation;
use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;
use Aurora\Module\Assistant\Conversation\Entity\Message;
use Aurora\Module\Assistant\Conversation\Entity\MessageInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;

/** Self-contained bundle for the Assistant module. @see AbstractAuroraModuleBundle */
final class AuroraAssistantBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Assistant';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            ConversationInterface::class => Conversation::class,
            MessageInterface::class => Message::class,
            AssistantMountPointInterface::class => AssistantMountPoint::class,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Planning;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Planning\Event\Entity\PlanningEvent;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;

/** Self-contained bundle for the Planning module. @see AbstractAuroraModuleBundle */
final class AuroraPlanningBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Planning';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            PlanningInterface::class => Planning::class,
            PlanningEventInterface::class => PlanningEvent::class,
        ];
    }
}

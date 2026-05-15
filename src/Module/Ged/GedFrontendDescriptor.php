<?php

declare(strict_types=1);

namespace Aurora\Module\Ged;

use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final class GedFrontendDescriptor implements FrontendInterface
{
    public function getSlug(): string
    {
        return 'ged';
    }

    public function getLabel(): string
    {
        return 'Ged';
    }

    public function getHomeRoute(): string
    {
        return 'frontend_ged_index';
    }

    public function getPriority(): int
    {
        return 2;
    }

    public function getModuleSettingKey(): string
    {
        return ModuleParameterEnum::GedFrontend->value;
    }

    public function getRoutePrefixes(): array
    {
        return ['frontend_ged_'];
    }
}

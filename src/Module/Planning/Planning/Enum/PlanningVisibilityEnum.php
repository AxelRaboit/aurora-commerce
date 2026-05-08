<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Enum;

enum PlanningVisibilityEnum: string
{
    case Private_ = 'private';
    case Agency = 'agency';
    case Public_ = 'public';
}

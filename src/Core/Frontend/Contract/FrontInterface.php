<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Contract;

interface FrontInterface
{
    public function getSlug(): string;

    public function getLabel(): string;

    public function getHomeRoute(): string;

    public function getPriority(): int;

    /** Returns the ApplicationParameterEnum key that enables/disables this front, or null if always available. */
    public function getModuleSettingKey(): ?string;
}

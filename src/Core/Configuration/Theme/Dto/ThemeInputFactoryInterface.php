<?php

declare(strict_types=1);

namespace Aurora\Core\Configuration\Theme\Dto;

interface ThemeInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ThemeInputInterface;
}

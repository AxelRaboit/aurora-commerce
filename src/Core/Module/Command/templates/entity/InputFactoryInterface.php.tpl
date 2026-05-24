<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Dto;

interface {{NAME}}InputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): {{NAME}}InputInterface;
}

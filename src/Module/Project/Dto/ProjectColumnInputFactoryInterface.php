<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

interface ProjectColumnInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectColumnInputInterface;
}

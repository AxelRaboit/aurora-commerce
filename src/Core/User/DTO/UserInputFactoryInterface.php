<?php

declare(strict_types=1);

namespace Aurora\Core\User\DTO;

interface UserInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): UserInputInterface;
}

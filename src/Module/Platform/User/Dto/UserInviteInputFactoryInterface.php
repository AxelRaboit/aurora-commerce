<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\User\Dto;

interface UserInviteInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): UserInviteInputInterface;
}

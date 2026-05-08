<?php

declare(strict_types=1);

namespace Aurora\Core\User\Serializer;

use Aurora\Core\User\Entity\User;

interface UserSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(User $user): array;

    /** @return array<string, mixed> */
    public function serializeWithSubordinates(User $user): array;
}

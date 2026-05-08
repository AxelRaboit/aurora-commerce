<?php

declare(strict_types=1);

namespace Aurora\Core\User\Serializer;

use Aurora\Core\User\Entity\CoreUserInterface;

interface UserSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(CoreUserInterface $user): array;

    /** @return array<string, mixed> */
    public function serializeWithSubordinates(CoreUserInterface $user): array;
}

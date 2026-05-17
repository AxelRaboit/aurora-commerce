<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\User\Serializer;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface UserSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(CoreUserInterface $user): array;

    /** @return array<string, mixed> */
    public function serializeWithSubordinates(CoreUserInterface $user): array;
}

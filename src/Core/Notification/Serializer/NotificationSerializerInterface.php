<?php

declare(strict_types=1);

namespace Aurora\Core\Notification\Serializer;

use Aurora\Core\Notification\Entity\NotificationInterface;

interface NotificationSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(NotificationInterface $notification): array;
}

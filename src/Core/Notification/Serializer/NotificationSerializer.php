<?php

declare(strict_types=1);

namespace Aurora\Core\Notification\Serializer;

use Aurora\Core\Notification\Entity\Notification;
use DateTimeInterface;

final readonly class NotificationSerializer
{
    /** @return array<string, mixed> */
    public function serialize(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'body' => $notification->getBody(),
            'url' => $notification->getUrl(),
            'data' => $notification->getData(),
            'readAt' => $notification->getReadAt()?->format(DateTimeInterface::ATOM),
            'createdAt' => $notification->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}

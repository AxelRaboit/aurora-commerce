<?php

declare(strict_types=1);

namespace Aurora\Core\Notification\Serializer;

use Aurora\Core\Notification\Entity\NotificationInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(NotificationSerializerInterface::class)]
class NotificationSerializer implements NotificationSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(NotificationInterface $notification): array
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

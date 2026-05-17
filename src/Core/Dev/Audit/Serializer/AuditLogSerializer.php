<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\Audit\Serializer;

use Aurora\Core\Dev\Audit\Entity\AuditLogInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AuditLogSerializerInterface::class)]
class AuditLogSerializer implements AuditLogSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AuditLogInterface $log): array
    {
        return [
            'id' => $log->getId(),
            'module' => $log->getModule(),
            'action' => $log->getAction(),
            'entityType' => $log->getEntityType(),
            'entityId' => $log->getEntityId(),
            'userId' => $log->getUserId(),
            'userEmail' => $log->getUserEmail(),
            'userName' => $log->getUserName(),
            'data' => $log->getData(),
            'createdAt' => $log->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}

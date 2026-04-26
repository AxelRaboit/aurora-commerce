<?php

declare(strict_types=1);

namespace App\Core\Audit\Serializer;

use App\Core\Audit\Entity\AuditLog;
use DateTimeInterface;

final readonly class AuditLogSerializer
{
    /** @return array<string, mixed> */
    public function serialize(AuditLog $log): array
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

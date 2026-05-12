<?php

declare(strict_types=1);

namespace Aurora\Core\Audit\Serializer;

use Aurora\Core\Audit\Entity\AuditLogInterface;

interface AuditLogSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AuditLogInterface $log): array;
}

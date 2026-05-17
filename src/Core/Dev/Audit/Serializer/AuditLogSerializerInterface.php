<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\Audit\Serializer;

use Aurora\Core\Dev\Audit\Entity\AuditLogInterface;

interface AuditLogSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AuditLogInterface $log): array;
}

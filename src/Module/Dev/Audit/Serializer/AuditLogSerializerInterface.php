<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\Audit\Serializer;

use Aurora\Module\Dev\Audit\Entity\AuditLogInterface;

interface AuditLogSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AuditLogInterface $log): array;
}

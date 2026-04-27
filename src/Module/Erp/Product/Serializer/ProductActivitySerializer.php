<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Serializer;

use Aurora\Core\Audit\Entity\AuditLog;
use DateTimeInterface;

final class ProductActivitySerializer
{
    /**
     * @param array{items: array<int, AuditLog>, total: int, page: int, totalPages: int} $result
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, totalPages: int}
     */
    public static function serialize(array $result): array
    {
        return [
            'items' => array_map(static fn (AuditLog $log): array => [
                'id' => $log->getId(),
                'action' => $log->getAction(),
                'userEmail' => $log->getUserEmail(),
                'userName' => $log->getUserName(),
                'data' => $log->getData(),
                'createdAt' => $log->getCreatedAt()->format(DateTimeInterface::ATOM),
            ], $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}

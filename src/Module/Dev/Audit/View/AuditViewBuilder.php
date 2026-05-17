<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\Audit\View;

use Aurora\Module\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Module\Dev\Audit\Serializer\AuditLogSerializer;

/**
 * Builds the Twig payload for the audit log dashboard tab. Centralises the
 * pagination + module filtering shape so the controller stays focused on the
 * HTTP lifecycle (XHR vs full-page rendering, query parsing).
 */
final readonly class AuditViewBuilder
{
    public function __construct(
        private AuditLogRepository $auditLogRepository,
        private AuditLogSerializer $auditLogSerializer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function auditPayload(int $page, ?string $module): array
    {
        $result = $this->auditLogRepository->findPaginated($page, 50, $module);

        return [
            'success' => true,
            'items' => array_map($this->auditLogSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'modules' => $this->auditLogRepository->findDistinctModules(),
            'module' => $module,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload): array
    {
        return [
            'tab' => 'audit',
            'audit' => $payload,
            'search' => '',
        ];
    }
}

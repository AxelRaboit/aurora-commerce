<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\View;

use Aurora\Core\Auth\Entity\AccessRequest;
use Aurora\Core\Auth\Repository\AccessRequestRepository;
use DateTimeInterface;

/**
 * Builds the Twig payloads for the dev access-requests dashboard tab.
 * Centralises the pagination + serialised item shape so the controller
 * stays focused on flow (XHR vs full page rendering, search query).
 */
final readonly class DevAccessRequestsViewBuilder
{
    public function __construct(private AccessRequestRepository $accessRequestRepository) {}

    /**
     * @return array<string, mixed>
     */
    public function accessRequestsPayload(int $page, ?string $search): array
    {
        $result = $this->accessRequestRepository->findPaginatedAdmin($page, search: $search);

        $items = array_map(
            fn (AccessRequest $accessRequest): array => [
                'id' => $accessRequest->getId(),
                'requesterEmail' => $accessRequest->getRequesterEmail(),
                'requesterName' => $accessRequest->getRequesterName(),
                'message' => $accessRequest->getMessage(),
                'status' => $accessRequest->getStatus()->value,
                'expiresAt' => $accessRequest->getExpiresAt()->format(DateTimeInterface::ATOM),
                'createdAt' => $accessRequest->getCreatedAt()->format(DateTimeInterface::ATOM),
            ],
            $result['items'],
        );

        return [
            'success' => true,
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload, ?string $search): array
    {
        return [
            'tab' => 'access_requests',
            'accessRequests' => $payload,
            'search' => $search ?? '',
        ];
    }
}

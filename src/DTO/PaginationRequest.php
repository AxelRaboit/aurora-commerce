<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

final readonly class PaginationRequest
{
    public function __construct(
        public int $page,
        public int $limit,
        public ?string $search,
    ) {}

    public static function fromRequest(Request $request, int $defaultLimit = 20): self
    {
        return new self(
            page: max(1, (int) $request->query->get('page', '1')),
            limit: $defaultLimit,
            search: mb_trim((string) $request->query->get('search', '')) ?: null,
        );
    }
}

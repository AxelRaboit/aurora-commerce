<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Dto;

final readonly class InvoiceLineDraft
{
    public function __construct(
        public string $label,
        public ?string $productCode = null,
        public ?string $unit = null,
        public ?string $quantity = null,
        public ?int $unitPriceCents = null,
        public ?int $vatRateBp = null,
        public ?int $totalNetCents = null,
        public ?int $totalGrossCents = null,
        public ?string $reference = null,
        public ?string $description = null,
        public ?int $discountCents = null,
        public ?string $origin = null,
    ) {}
}

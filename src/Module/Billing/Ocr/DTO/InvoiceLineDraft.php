<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\DTO;

final readonly class InvoiceLineDraft
{
    public function __construct(
        public string $label,
        public ?string $sku,
        public ?string $unit,
        public ?string $quantity,
        public ?int $unitPriceCents,
        public ?int $vatRateBp,
        public ?int $totalNetCents,
        public ?int $totalGrossCents,
    ) {}
}

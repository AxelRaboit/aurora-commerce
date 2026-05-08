<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Contract;

use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use InvalidArgumentException;

interface TiersManagerInterface
{
    public function delete(Tiers $tiers): void;

    /**
     * @throws InvalidArgumentException with a translation key when the field is unknown or invalid
     */
    public function updateField(Tiers $tiers, string $field, mixed $value): void;

    /**
     * Resolve an existing supplier Tiers (by VAT number, then by name) or create one from the OCR draft.
     * Returns null when no supplier name could be extracted.
     */
    public function findOrCreateSupplierFromDraft(InvoiceDraft $draft): ?Tiers;

    /**
     * Resolve an existing client Tiers (by VAT number, then by name) or create one from the OCR draft.
     * Returns null when no buyer name could be extracted.
     */
    public function findOrCreateClientFromDraft(InvoiceDraft $draft): ?Tiers;

    public function findOrCreate(TiersTypeEnum $type, string $name, ?string $vatNumber): Tiers;
}

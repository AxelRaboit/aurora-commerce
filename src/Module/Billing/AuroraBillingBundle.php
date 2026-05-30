<?php

declare(strict_types=1);

namespace Aurora\Module\Billing;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;

/** Self-contained bundle for the Billing module. @see AbstractAuroraModuleBundle */
final class AuroraBillingBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Billing';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            InvoiceInterface::class => Invoice::class,
            InvoiceLineInterface::class => InvoiceLine::class,
            TiersInterface::class => Tiers::class,
            OcrJobInterface::class => OcrJob::class,
        ];
    }
}

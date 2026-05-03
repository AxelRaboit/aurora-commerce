<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Enum;

enum TiersTypeEnum: string
{
    case Supplier = 'supplier';
    case Client = 'client';
    case Partner = 'partner';
    case Subcontractor = 'subcontractor';
    case Other = 'other';

    public function getLabelKey(): string
    {
        return 'admin.billing.tiers.type.'.$this->value;
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Enum;

enum ProductStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}

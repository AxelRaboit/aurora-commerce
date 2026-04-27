<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Enum;

enum OrderStatusEnum: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}

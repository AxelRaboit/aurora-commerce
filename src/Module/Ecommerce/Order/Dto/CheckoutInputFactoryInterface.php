<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Dto;

interface CheckoutInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): CheckoutInputInterface;
}

<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Dto;

interface ProductInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProductInputInterface;
}

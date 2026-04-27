<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Contract;

use Aurora\Module\Erp\Product\DTO\ProductInput;
use Aurora\Module\Erp\Product\Entity\Product;

interface ProductManagerInterface
{
    public function create(ProductInput $input): Product;

    public function update(Product $product, ProductInput $input): void;

    public function delete(Product $product): void;
}

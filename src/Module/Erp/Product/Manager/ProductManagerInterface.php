<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Manager;

use Aurora\Module\Erp\Product\Dto\ProductInputInterface;
use Aurora\Module\Erp\Product\Entity\ProductInterface;

interface ProductManagerInterface
{
    public function create(ProductInputInterface $input): ProductInterface;

    public function update(ProductInterface $product, ProductInputInterface $input): void;

    public function delete(ProductInterface $product): void;
}

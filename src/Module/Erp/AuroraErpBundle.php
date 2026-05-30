<?php

declare(strict_types=1);

namespace Aurora\Module\Erp;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Entity\ProductInterface;

/**
 * Self-contained bundle for the Erp module. Ships together with Ecommerce in
 * the `aurora-commerce` package: a Listing sells a Product, so the two form a
 * single domain (cat. E of decoupling_strategy.md). They register as two
 * bundles but install as one package.
 *
 * @see AbstractAuroraModuleBundle
 */
final class AuroraErpBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Erp';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            ProductInterface::class => Product::class,
        ];
    }
}

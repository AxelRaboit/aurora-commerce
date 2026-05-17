<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Product())->getId());
    }

    public function testDefaultValues(): void
    {
        $product = new Product();

        self::assertNull($product->getDescription());
        self::assertNull($product->getPriceCents());
        self::assertSame(CurrencyEnum::EUR, $product->getCurrency());
        self::assertSame(ProductStatusEnum::Draft, $product->getStatus());
        self::assertSame(ProductTypeEnum::Physical, $product->getType());
        self::assertNull($product->getImage());
        self::assertNull($product->getStockQuantity());
    }

    public function testNameAndReferenceGettersAndSetters(): void
    {
        $product = (new Product())->setName('Widget')->setReference('WGT-001');

        self::assertSame('Widget', $product->getName());
        self::assertSame('WGT-001', $product->getReference());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $product = (new Product())->setDescription('A widget');

        self::assertSame('A widget', $product->getDescription());

        $product->setDescription(null);
        self::assertNull($product->getDescription());
    }

    public function testPriceAndCurrency(): void
    {
        $product = (new Product())->setPriceCents(1999)->setCurrency(CurrencyEnum::USD);

        self::assertSame(1999, $product->getPriceCents());
        self::assertSame(CurrencyEnum::USD, $product->getCurrency());
    }

    public function testStatusAndType(): void
    {
        $product = (new Product())->setStatus(ProductStatusEnum::Active)->setType(ProductTypeEnum::Digital);

        self::assertSame(ProductStatusEnum::Active, $product->getStatus());
        self::assertSame(ProductTypeEnum::Digital, $product->getType());
    }

    public function testImageGetterAndSetter(): void
    {
        $image = $this->createStub(MediaInterface::class);
        $product = (new Product())->setImage($image);

        self::assertSame($image, $product->getImage());
    }

    public function testIsStockTrackedIsFalseByDefault(): void
    {
        self::assertFalse((new Product())->isStockTracked());
    }

    public function testIsStockTrackedTrueWhenQuantitySet(): void
    {
        $product = (new Product())->setStockQuantity(10);

        self::assertTrue($product->isStockTracked());
        self::assertSame(10, $product->getStockQuantity());
    }

    public function testIsInStockReturnsTrueWhenNotTracked(): void
    {
        self::assertTrue((new Product())->isInStock(100));
    }

    public function testIsInStockReturnsTrueWhenEnoughStock(): void
    {
        $product = (new Product())->setStockQuantity(10);

        self::assertTrue($product->isInStock(5));
        self::assertTrue($product->isInStock(10));
    }

    public function testIsInStockReturnsFalseWhenInsufficient(): void
    {
        $product = (new Product())->setStockQuantity(5);

        self::assertFalse($product->isInStock(10));
    }

    public function testDecrementStockReducesQuantity(): void
    {
        $product = (new Product())->setStockQuantity(10);
        $product->decrementStock(3);

        self::assertSame(7, $product->getStockQuantity());
    }

    public function testDecrementStockClampsToZero(): void
    {
        $product = (new Product())->setStockQuantity(5);
        $product->decrementStock(10);

        self::assertSame(0, $product->getStockQuantity());
    }

    public function testDecrementStockNoOpWhenNotTracked(): void
    {
        $product = new Product();
        $product->decrementStock(5);

        self::assertNull($product->getStockQuantity());
    }
}

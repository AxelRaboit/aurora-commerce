<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use PHPUnit\Framework\TestCase;

final class DocumentCategoryTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new DocumentCategory())->getId());
    }

    public function testDescriptionIsNullByDefault(): void
    {
        self::assertNull((new DocumentCategory())->getDescription());
    }

    public function testNameGetterAndSetter(): void
    {
        $category = (new DocumentCategory())->setName('Legal');

        self::assertSame('Legal', $category->getName());
    }

    public function testSlugGetterAndSetter(): void
    {
        $category = (new DocumentCategory())->setSlug('legal');

        self::assertSame('legal', $category->getSlug());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $category = (new DocumentCategory())->setDescription('Legal documents');

        self::assertSame('Legal documents', $category->getDescription());

        $category->setDescription(null);
        self::assertNull($category->getDescription());
    }
}

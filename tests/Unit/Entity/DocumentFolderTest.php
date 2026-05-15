<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolder;
use PHPUnit\Framework\TestCase;

final class DocumentFolderTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new DocumentFolder())->getId());
    }

    public function testChildrenCollectionInitialized(): void
    {
        self::assertCount(0, (new DocumentFolder())->getChildren());
    }

    public function testDefaultValues(): void
    {
        $folder = new DocumentFolder();

        self::assertNull($folder->getParent());
        self::assertSame(0, $folder->getPosition());
    }

    public function testNameGetterAndSetter(): void
    {
        $folder = (new DocumentFolder())->setName('Contracts');

        self::assertSame('Contracts', $folder->getName());
    }

    public function testPositionGetterAndSetter(): void
    {
        $folder = (new DocumentFolder())->setPosition(3);

        self::assertSame(3, $folder->getPosition());
    }

    public function testParentGetterAndSetter(): void
    {
        $parent = new DocumentFolder();
        $folder = (new DocumentFolder())->setParent($parent);

        self::assertSame($parent, $folder->getParent());

        $folder->setParent(null);
        self::assertNull($folder->getParent());
    }
}

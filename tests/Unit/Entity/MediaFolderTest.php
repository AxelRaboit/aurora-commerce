<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Media\Library\Entity\MediaFolder;
use PHPUnit\Framework\TestCase;

final class MediaFolderTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new MediaFolder())->getId());
    }

    public function testChildrenCollectionInitialized(): void
    {
        self::assertCount(0, (new MediaFolder())->getChildren());
    }

    public function testDefaultValues(): void
    {
        $folder = new MediaFolder();

        self::assertNull($folder->getReference());
        self::assertNull($folder->getParent());
        self::assertSame(0, $folder->getPosition());
    }

    public function testNameGetterAndSetter(): void
    {
        $folder = (new MediaFolder())->setName('Photos');

        self::assertSame('Photos', $folder->getName());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $folder = (new MediaFolder())->setReference('MF-001');

        self::assertSame('MF-001', $folder->getReference());

        $folder->setReference(null);
        self::assertNull($folder->getReference());
    }

    public function testPositionGetterAndSetter(): void
    {
        $folder = (new MediaFolder())->setPosition(5);

        self::assertSame(5, $folder->getPosition());
    }

    public function testParentGetterAndSetter(): void
    {
        $parent = new MediaFolder();
        $folder = (new MediaFolder())->setParent($parent);

        self::assertSame($parent, $folder->getParent());

        $folder->setParent(null);
        self::assertNull($folder->getParent());
    }

    public function testGetAncestorsBuildsRootFirstChain(): void
    {
        $root = new MediaFolder();
        $middle = (new MediaFolder())->setParent($root);
        $leaf = (new MediaFolder())->setParent($middle);

        self::assertSame([], $root->getAncestors());
        self::assertSame([$root], $middle->getAncestors());
        self::assertSame([$root, $middle], $leaf->getAncestors());
    }

    public function testIsDescendantOfDetectsAncestor(): void
    {
        $root = new MediaFolder();
        $middle = (new MediaFolder())->setParent($root);
        $leaf = (new MediaFolder())->setParent($middle);
        $sibling = new MediaFolder();

        self::assertTrue($leaf->isDescendantOf($root));
        self::assertTrue($leaf->isDescendantOf($middle));
        self::assertFalse($leaf->isDescendantOf($sibling));
        self::assertFalse($root->isDescendantOf($leaf));
    }
}

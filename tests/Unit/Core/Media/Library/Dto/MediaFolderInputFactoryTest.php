<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Media\Library\Dto;

use Aurora\Core\Media\Library\Dto\MediaFolderInputFactory;
use PHPUnit\Framework\TestCase;

final class MediaFolderInputFactoryTest extends TestCase
{
    public function testFromArrayWithValidData(): void
    {
        $input = (new MediaFolderInputFactory())->fromArray(['name' => '  Photos  ', 'parentId' => 42]);

        self::assertSame('Photos', $input->getName());
        self::assertSame(42, $input->getParentId());
    }

    public function testFromArrayDefaults(): void
    {
        $input = (new MediaFolderInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertNull($input->getParentId());
    }

    public function testFromArrayNullsZeroOrNegativeParentId(): void
    {
        $input = (new MediaFolderInputFactory())->fromArray(['name' => 'X', 'parentId' => 0]);

        self::assertNull($input->getParentId());
    }
}

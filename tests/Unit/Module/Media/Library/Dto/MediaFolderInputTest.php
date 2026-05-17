<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Media\Library\Dto;

use Aurora\Module\Media\Library\Dto\MediaFolderInput;
use PHPUnit\Framework\TestCase;

final class MediaFolderInputTest extends TestCase
{
    public function testGetNameReturnsConstructorValue(): void
    {
        self::assertSame('Photos', (new MediaFolderInput('Photos'))->getName());
    }

    public function testParentIdIsNullByDefault(): void
    {
        self::assertNull((new MediaFolderInput('Photos'))->getParentId());
    }

    public function testGetParentIdReturnsConstructorValue(): void
    {
        self::assertSame(42, (new MediaFolderInput('Photos', 42))->getParentId());
    }
}

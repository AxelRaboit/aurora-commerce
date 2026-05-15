<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Enum;

use Aurora\Module\Photo\Enum\PhotoCacheDirEnum;
use PHPUnit\Framework\TestCase;

final class PhotoCacheDirEnumTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('watermarks', PhotoCacheDirEnum::Watermarks->value);
        self::assertSame('degraded', PhotoCacheDirEnum::Degraded->value);
        self::assertCount(2, PhotoCacheDirEnum::cases());
    }
}

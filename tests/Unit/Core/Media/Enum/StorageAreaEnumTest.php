<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Media\Enum;

use Aurora\Core\Media\Enum\StorageAreaEnum;
use PHPUnit\Framework\TestCase;

final class StorageAreaEnumTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('media', StorageAreaEnum::Media->value);
        self::assertSame('ocr', StorageAreaEnum::Ocr->value);
        self::assertSame('photo', StorageAreaEnum::Photo->value);
        self::assertSame('users', StorageAreaEnum::Users->value);
        self::assertCount(4, StorageAreaEnum::cases());
    }
}

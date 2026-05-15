<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\MountPoint\Enum;

use Aurora\Core\MountPoint\Enum\MountPointTypeEnum;
use PHPUnit\Framework\TestCase;

final class MountPointTypeEnumTest extends TestCase
{
    public function testGetLabelReturnsHumanReadableNames(): void
    {
        self::assertSame('Database', MountPointTypeEnum::Database->getLabel());
        self::assertSame('API', MountPointTypeEnum::Api->getLabel());
        self::assertSame('SFTP', MountPointTypeEnum::Sftp->getLabel());
    }

    public function testCases(): void
    {
        self::assertSame('database', MountPointTypeEnum::Database->value);
        self::assertSame('api', MountPointTypeEnum::Api->value);
        self::assertSame('sftp', MountPointTypeEnum::Sftp->value);
    }
}

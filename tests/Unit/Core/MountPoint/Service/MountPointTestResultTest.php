<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\MountPoint\Service;

use Aurora\Core\MountPoint\Service\MountPointTestResult;
use PHPUnit\Framework\TestCase;

final class MountPointTestResultTest extends TestCase
{
    public function testSuccessReturnsSuccessResult(): void
    {
        $result = MountPointTestResult::success();

        self::assertTrue($result->success);
        self::assertNull($result->message);
    }

    public function testSuccessWithMessage(): void
    {
        $result = MountPointTestResult::success('Connected');

        self::assertTrue($result->success);
        self::assertSame('Connected', $result->message);
    }

    public function testFailureReturnsFailureResult(): void
    {
        $result = MountPointTestResult::failure('Connection refused');

        self::assertFalse($result->success);
        self::assertSame('Connection refused', $result->message);
    }
}

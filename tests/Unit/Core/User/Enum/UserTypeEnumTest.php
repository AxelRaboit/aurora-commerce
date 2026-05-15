<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\User\Enum;

use Aurora\Core\User\Enum\UserTypeEnum;
use PHPUnit\Framework\TestCase;

final class UserTypeEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesValue(): void
    {
        self::assertSame('backend.users.type.backend', UserTypeEnum::Backend->getLabelKey());
        self::assertSame('backend.users.type.frontend', UserTypeEnum::Frontend->getLabelKey());
    }
}

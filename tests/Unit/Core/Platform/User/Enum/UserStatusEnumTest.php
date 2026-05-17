<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Platform\User\Enum;

use Aurora\Core\Platform\User\Enum\UserStatusEnum;
use PHPUnit\Framework\TestCase;

final class UserStatusEnumTest extends TestCase
{
    public function testCaseValues(): void
    {
        self::assertSame('active', UserStatusEnum::Active->value);
        self::assertSame('invited', UserStatusEnum::Invited->value);
        self::assertSame('disabled', UserStatusEnum::Disabled->value);
        self::assertSame('pending_verification', UserStatusEnum::PendingVerification->value);
    }

    public function testGetLabelKeyPrefixesCaseValue(): void
    {
        self::assertSame('backend.users.status.active', UserStatusEnum::Active->getLabelKey());
        self::assertSame('backend.users.status.invited', UserStatusEnum::Invited->getLabelKey());
        self::assertSame('backend.users.status.disabled', UserStatusEnum::Disabled->getLabelKey());
        self::assertSame('backend.users.status.pending_verification', UserStatusEnum::PendingVerification->getLabelKey());
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Service;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Core\Platform\User\Enum\UserRoleEnum;
use Aurora\Module\Editorial\Post\Service\PostAccessService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class PostAccessServiceTest extends TestCase
{
    public function testScopedAuthorIdReturnsNullForDev(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn (string $role): bool => $role === UserRoleEnum::Dev->value,
        );

        self::assertNull((new PostAccessService($security))->scopedAuthorId());
    }

    public function testScopedAuthorIdReturnsNullForAdmin(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn (string $role): bool => $role === UserRoleEnum::Admin->value,
        );

        self::assertNull((new PostAccessService($security))->scopedAuthorId());
    }

    public function testScopedAuthorIdReturnsCurrentUserIdForOtherRoles(): void
    {
        $user = $this->createStub(CoreUserInterface::class);
        $user->method('getId')->willReturn(42);

        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(false);
        $security->method('getUser')->willReturn($user);

        self::assertSame(42, (new PostAccessService($security))->scopedAuthorId());
    }

    public function testScopedAuthorIdReturnsNullWhenNoUser(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(false);
        $security->method('getUser')->willReturn(null);

        self::assertNull((new PostAccessService($security))->scopedAuthorId());
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Platform\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Platform\User\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ResetPasswordRequestTest extends TestCase
{
    private function makeRequest(?DateTimeImmutable $expiresAt = null): ResetPasswordRequest
    {
        return new ResetPasswordRequest(
            new User(),
            'selector-abc',
            'hash-xyz',
            $expiresAt ?? new DateTimeImmutable('+1 hour'),
        );
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->makeRequest()->getId());
    }

    public function testConstructorFields(): void
    {
        $request = $this->makeRequest();

        self::assertInstanceOf(User::class, $request->getUser());
        self::assertSame('selector-abc', $request->getSelector());
        self::assertSame('hash-xyz', $request->getHashedToken());
    }

    public function testIsExpiredForPastDate(): void
    {
        $request = $this->makeRequest(new DateTimeImmutable('-1 hour'));

        self::assertTrue($request->isExpired());
    }

    public function testIsExpiredForFutureDate(): void
    {
        $request = $this->makeRequest(new DateTimeImmutable('+1 hour'));

        self::assertFalse($request->isExpired());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $request = $this->makeRequest();

        self::assertNull($request->getReference());

        $request->setReference('RPR-001');
        self::assertSame('RPR-001', $request->getReference());
    }

    public function testGetExpiresAtReturnsConstructorValue(): void
    {
        $date = new DateTimeImmutable('2026-01-15 10:00:00');
        $request = $this->makeRequest($date);

        self::assertSame($date, $request->getExpiresAt());
    }
}

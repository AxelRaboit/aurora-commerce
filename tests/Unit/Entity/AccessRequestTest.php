<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Auth\Entity\AccessRequest;
use Aurora\Core\Auth\Enum\AccessRequestStatusEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AccessRequestTest extends TestCase
{
    private function makeRequest(?DateTimeImmutable $expiresAt = null): AccessRequest
    {
        return new AccessRequest('user@example.com', $expiresAt ?? new DateTimeImmutable('+7 days'));
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->makeRequest()->getId());
    }

    public function testDefaultStatusIsPending(): void
    {
        $request = $this->makeRequest();

        self::assertSame(AccessRequestStatusEnum::Pending, $request->getStatus());
        self::assertTrue($request->isPending());
        self::assertFalse($request->isApproved());
        self::assertFalse($request->isRejected());
    }

    public function testIsApproved(): void
    {
        $request = $this->makeRequest();
        $request->setStatus(AccessRequestStatusEnum::Approved);

        self::assertTrue($request->isApproved());
        self::assertFalse($request->isPending());
    }

    public function testIsRejected(): void
    {
        $request = $this->makeRequest();
        $request->setStatus(AccessRequestStatusEnum::Rejected);

        self::assertTrue($request->isRejected());
    }

    public function testIsExpiredReturnsTrueForPastDate(): void
    {
        $request = $this->makeRequest(new DateTimeImmutable('-1 day'));

        self::assertTrue($request->isExpired());
    }

    public function testIsExpiredReturnsFalseForFutureDate(): void
    {
        $request = $this->makeRequest(new DateTimeImmutable('+1 day'));

        self::assertFalse($request->isExpired());
    }

    public function testRequesterEmailFromConstructor(): void
    {
        $request = $this->makeRequest();

        self::assertSame('user@example.com', $request->getRequesterEmail());
    }

    public function testRequesterNameGetterAndSetter(): void
    {
        $request = $this->makeRequest();

        self::assertNull($request->getRequesterName());

        $request->setRequesterName('John Doe');
        self::assertSame('John Doe', $request->getRequesterName());
    }

    public function testMessageGetterAndSetter(): void
    {
        $request = $this->makeRequest();

        self::assertNull($request->getMessage());

        $request->setMessage('Please grant me access');
        self::assertSame('Please grant me access', $request->getMessage());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $request = $this->makeRequest();

        self::assertNull($request->getReference());

        $request->setReference('AR-001');
        self::assertSame('AR-001', $request->getReference());
    }
}

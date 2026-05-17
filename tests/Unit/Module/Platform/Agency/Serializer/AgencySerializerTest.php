<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Agency\Serializer;

use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Platform\Agency\Serializer\AgencySerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AgencySerializerTest extends TestCase
{
    public function testSerializeReturnsExpectedShape(): void
    {
        $agency = $this->createStub(AgencyInterface::class);
        $agency->method('getId')->willReturn(1);
        $agency->method('getName')->willReturn('Aurora Studio');
        $agency->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-15T10:00:00+00:00'));

        $result = (new AgencySerializer())->serialize($agency);

        self::assertSame(1, $result['id']);
        self::assertSame('Aurora Studio', $result['name']);
        self::assertSame('2026-01-15T10:00:00+00:00', $result['createdAt']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $agency = $this->createStub(AgencyInterface::class);
        $agency->method('getId')->willReturn(1);
        $agency->method('getName')->willReturn('X');
        $agency->method('getCreatedAt')->willReturn(new DateTimeImmutable());

        $result = (new AgencySerializer())->serialize($agency);

        self::assertSame(['id', 'name', 'createdAt'], array_keys($result));
    }
}

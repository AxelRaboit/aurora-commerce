<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Service\Serializer;

use Aurora\Module\Platform\Service\Entity\ServiceInterface;
use Aurora\Module\Platform\Service\Serializer\ServiceSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ServiceSerializerTest extends TestCase
{
    public function testSerializeReturnsExpectedShape(): void
    {
        $service = $this->createStub(ServiceInterface::class);
        $service->method('getId')->willReturn(7);
        $service->method('getName')->willReturn('Web Development');
        $service->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-15T10:00:00+00:00'));

        $result = (new ServiceSerializer())->serialize($service);

        self::assertSame(7, $result['id']);
        self::assertSame('Web Development', $result['name']);
        self::assertSame('2026-01-15T10:00:00+00:00', $result['createdAt']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $service = $this->createStub(ServiceInterface::class);
        $service->method('getId')->willReturn(1);
        $service->method('getName')->willReturn('X');
        $service->method('getCreatedAt')->willReturn(new DateTimeImmutable());

        $result = (new ServiceSerializer())->serialize($service);

        self::assertSame(['id', 'name', 'createdAt'], array_keys($result));
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Planning\Serializer;

use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Aurora\Module\Planning\Planning\Serializer\PlanningSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PlanningSerializerTest extends TestCase
{
    private function makePlanning(?CoreUserInterface $owner = null, ?AgencyInterface $agency = null): PlanningInterface
    {
        $planning = $this->createStub(PlanningInterface::class);
        $planning->method('getId')->willReturn(1);
        $planning->method('getName')->willReturn('Team Calendar');
        $planning->method('getDescription')->willReturn('Shared');
        $planning->method('getColor')->willReturn('#3b82f6');
        $planning->method('getTimezone')->willReturn('Europe/Paris');
        $planning->method('getVisibility')->willReturn(PlanningVisibilityEnum::Private_);
        $planning->method('getOwner')->willReturn($owner);
        $planning->method('getAgency')->willReturn($agency);
        $planning->method('getCreatedAt')->willReturn(new DateTimeImmutable('2026-01-01'));
        $planning->method('getUpdatedAt')->willReturn(new DateTimeImmutable('2026-01-02'));

        return $planning;
    }

    public function testSerializeReturnsExpectedShape(): void
    {
        $result = (new PlanningSerializer())->serialize($this->makePlanning());

        self::assertSame(1, $result['id']);
        self::assertSame('Team Calendar', $result['name']);
        self::assertSame('#3b82f6', $result['color']);
        self::assertSame('Europe/Paris', $result['timezone']);
        self::assertSame('private', $result['visibility']);
        self::assertNull($result['owner']);
        self::assertNull($result['agency']);
    }

    public function testSerializeIncludesOwner(): void
    {
        $owner = $this->createStub(CoreUserInterface::class);
        $owner->method('getId')->willReturn(7);
        $owner->method('getName')->willReturn('Jane Doe');

        $result = (new PlanningSerializer())->serialize($this->makePlanning(owner: $owner));

        self::assertSame(['id' => 7, 'name' => 'Jane Doe'], $result['owner']);
    }

    public function testSerializeIncludesAgency(): void
    {
        $agency = $this->createStub(AgencyInterface::class);
        $agency->method('getId')->willReturn(3);
        $agency->method('getName')->willReturn('Aurora Studio');

        $result = (new PlanningSerializer())->serialize($this->makePlanning(agency: $agency));

        self::assertSame(['id' => 3, 'name' => 'Aurora Studio'], $result['agency']);
    }
}

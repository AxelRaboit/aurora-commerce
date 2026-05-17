<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Platform\Agency\Entity\Agency;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use PHPUnit\Framework\TestCase;

final class PlanningTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Planning())->getId());
    }

    public function testEventsCollectionInitialized(): void
    {
        self::assertCount(0, (new Planning())->getEvents());
    }

    public function testDefaultValues(): void
    {
        $planning = new Planning();

        self::assertNull($planning->getDescription());
        self::assertSame('#3b82f6', $planning->getColor());
        self::assertSame('Europe/Paris', $planning->getTimezone());
        self::assertSame(PlanningVisibilityEnum::Private_, $planning->getVisibility());
        self::assertNull($planning->getOwner());
        self::assertNull($planning->getAgency());
    }

    public function testNameGetterAndSetter(): void
    {
        $planning = (new Planning())->setName('Team Calendar');

        self::assertSame('Team Calendar', $planning->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $planning = (new Planning())->setDescription('Shared calendar');

        self::assertSame('Shared calendar', $planning->getDescription());

        $planning->setDescription(null);
        self::assertNull($planning->getDescription());
    }

    public function testColorAndTimezone(): void
    {
        $planning = (new Planning())->setColor('#ff0000')->setTimezone('America/New_York');

        self::assertSame('#ff0000', $planning->getColor());
        self::assertSame('America/New_York', $planning->getTimezone());
    }

    public function testVisibilityGetterAndSetter(): void
    {
        $planning = (new Planning())->setVisibility(PlanningVisibilityEnum::Agency);

        self::assertSame(PlanningVisibilityEnum::Agency, $planning->getVisibility());
    }

    public function testOwnerGetterAndSetter(): void
    {
        $owner = new User();
        $planning = (new Planning())->setOwner($owner);

        self::assertSame($owner, $planning->getOwner());
    }

    public function testAgencyGetterAndSetter(): void
    {
        $agency = new Agency();
        $planning = (new Planning())->setAgency($agency);

        self::assertSame($agency, $planning->getAgency());

        $planning->setAgency(null);
        self::assertNull($planning->getAgency());
    }
}

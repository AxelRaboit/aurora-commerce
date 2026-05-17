<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Platform\Agency\Entity\Agency;
use PHPUnit\Framework\TestCase;

final class AgencyTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Agency())->getId());
    }

    public function testNameGetterAndSetter(): void
    {
        $agency = (new Agency())->setName('Aurora Studio');

        self::assertSame('Aurora Studio', $agency->getName());
    }
}

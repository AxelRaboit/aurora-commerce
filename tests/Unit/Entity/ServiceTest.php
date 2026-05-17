<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Platform\Service\Entity\Service;
use PHPUnit\Framework\TestCase;

final class ServiceTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Service())->getId());
    }

    public function testNameGetterAndSetter(): void
    {
        $service = (new Service())->setName('Web Development');

        self::assertSame('Web Development', $service->getName());
    }
}

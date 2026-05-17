<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Agency\Dto;

use Aurora\Module\Platform\Agency\Dto\AgencyInputFactory;
use PHPUnit\Framework\TestCase;

final class AgencyInputFactoryTest extends TestCase
{
    public function testFromArrayTrimsName(): void
    {
        $input = (new AgencyInputFactory())->fromArray(['name' => '  Aurora Studio  ']);

        self::assertSame('Aurora Studio', $input->getName());
    }

    public function testFromArrayWithMissingName(): void
    {
        $input = (new AgencyInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
    }
}

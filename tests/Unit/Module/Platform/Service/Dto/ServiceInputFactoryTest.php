<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Service\Dto;

use Aurora\Module\Platform\Service\Dto\ServiceInputFactory;
use PHPUnit\Framework\TestCase;

final class ServiceInputFactoryTest extends TestCase
{
    public function testFromArrayTrimsName(): void
    {
        $input = (new ServiceInputFactory())->fromArray(['name' => '  Hello  ']);

        self::assertSame('Hello', $input->getName());
    }

    public function testFromArrayWithMissingNameReturnsEmpty(): void
    {
        $input = (new ServiceInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
    }
}

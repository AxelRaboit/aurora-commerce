<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectLabelInputFactory;
use PHPUnit\Framework\TestCase;

final class ProjectLabelInputFactoryTest extends TestCase
{
    public function testFromArrayTrimsNameAndColor(): void
    {
        $input = (new ProjectLabelInputFactory())->fromArray([
            'name' => '  Bug  ',
            'color' => '  rose  ',
        ]);

        self::assertSame('Bug', $input->getName());
        self::assertSame('rose', $input->getColor());
    }

    public function testFromArrayWithMissingFieldsUsesDefaults(): void
    {
        $input = (new ProjectLabelInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertSame('accent', $input->getColor());
    }
}

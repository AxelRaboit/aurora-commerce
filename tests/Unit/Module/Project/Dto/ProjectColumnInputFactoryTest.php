<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectColumnInputFactory;
use PHPUnit\Framework\TestCase;

final class ProjectColumnInputFactoryTest extends TestCase
{
    public function testFromArrayTrimsLabel(): void
    {
        $input = (new ProjectColumnInputFactory())->fromArray(['label' => '  In Progress  ']);

        self::assertSame('In Progress', $input->getLabel());
    }

    public function testFromArrayWithMissingLabel(): void
    {
        $input = (new ProjectColumnInputFactory())->fromArray([]);

        self::assertSame('', $input->getLabel());
    }
}

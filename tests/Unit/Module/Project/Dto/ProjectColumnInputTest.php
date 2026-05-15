<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectColumnInput;
use PHPUnit\Framework\TestCase;

final class ProjectColumnInputTest extends TestCase
{
    public function testDefaultLabel(): void
    {
        self::assertSame('', (new ProjectColumnInput())->getLabel());
    }

    public function testGetLabelReturnsConstructorValue(): void
    {
        self::assertSame('In Progress', (new ProjectColumnInput('In Progress'))->getLabel());
    }
}

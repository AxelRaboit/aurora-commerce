<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectLabelInput;
use PHPUnit\Framework\TestCase;

final class ProjectLabelInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new ProjectLabelInput();

        self::assertSame('', $input->getName());
        self::assertSame('accent', $input->getColor());
    }

    public function testConstructorValues(): void
    {
        $input = new ProjectLabelInput('Bug', 'rose');

        self::assertSame('Bug', $input->getName());
        self::assertSame('rose', $input->getColor());
    }
}

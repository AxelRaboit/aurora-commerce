<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectTaskCommentInput;
use PHPUnit\Framework\TestCase;

final class ProjectTaskCommentInputTest extends TestCase
{
    public function testDefaultContent(): void
    {
        self::assertSame('', (new ProjectTaskCommentInput())->getContent());
    }

    public function testGetContentReturnsConstructorValue(): void
    {
        self::assertSame('Hello world', (new ProjectTaskCommentInput('Hello world'))->getContent());
    }
}

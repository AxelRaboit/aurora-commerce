<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectTaskCommentInputFactory;
use PHPUnit\Framework\TestCase;

final class ProjectTaskCommentInputFactoryTest extends TestCase
{
    public function testFromArrayTrimsContent(): void
    {
        $input = (new ProjectTaskCommentInputFactory())->fromArray(['content' => '  Hello  ']);

        self::assertSame('Hello', $input->getContent());
    }

    public function testFromArrayWithMissingContent(): void
    {
        $input = (new ProjectTaskCommentInputFactory())->fromArray([]);

        self::assertSame('', $input->getContent());
    }
}

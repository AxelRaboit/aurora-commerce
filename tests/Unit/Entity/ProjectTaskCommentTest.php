<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use PHPUnit\Framework\TestCase;

final class ProjectTaskCommentTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectTaskComment())->getId());
    }

    public function testTaskGetterAndSetter(): void
    {
        $task = $this->createStub(ProjectTaskInterface::class);
        $comment = (new ProjectTaskComment())->setTask($task);

        self::assertSame($task, $comment->getTask());
    }

    public function testAuthorGetterAndSetter(): void
    {
        $user = new User();
        $comment = (new ProjectTaskComment())->setAuthor($user);

        self::assertSame($user, $comment->getAuthor());
    }

    public function testContentGetterAndSetter(): void
    {
        $comment = (new ProjectTaskComment())->setContent('Looks good.');

        self::assertSame('Looks good.', $comment->getContent());
    }

    public function testSettersReturnSelf(): void
    {
        $comment = new ProjectTaskComment();

        self::assertSame($comment, $comment->setTask($this->createStub(ProjectTaskInterface::class)));
        self::assertSame($comment, $comment->setAuthor(new User()));
        self::assertSame($comment, $comment->setContent('c'));
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Platform\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class PostRevisionTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PostRevision())->getId());
    }

    public function testDefaultValues(): void
    {
        $revision = new PostRevision();

        self::assertSame([], $revision->getSnapshot());
        self::assertNull($revision->getAuthor());
    }

    public function testPostGetterAndSetter(): void
    {
        $post = new Post();
        $revision = (new PostRevision())->setPost($post);

        self::assertSame($post, $revision->getPost());
    }

    public function testPostVersionGetterAndSetter(): void
    {
        $revision = (new PostRevision())->setPostVersion(5);

        self::assertSame(5, $revision->getPostVersion());
    }

    public function testStatusGetterAndSetter(): void
    {
        $revision = (new PostRevision())->setStatus(PostStatusEnum::Published);

        self::assertSame(PostStatusEnum::Published, $revision->getStatus());
    }

    public function testSnapshotGetterAndSetter(): void
    {
        $snapshot = ['title' => 'Hello', 'content' => 'World'];
        $revision = (new PostRevision())->setSnapshot($snapshot);

        self::assertSame($snapshot, $revision->getSnapshot());
    }

    public function testAuthorGetterAndSetter(): void
    {
        $user = new User();
        $revision = (new PostRevision())->setAuthor($user);

        self::assertSame($user, $revision->getAuthor());

        $revision->setAuthor(null);
        self::assertNull($revision->getAuthor());
    }
}

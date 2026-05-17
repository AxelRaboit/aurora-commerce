<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Comment\Serializer;

use Aurora\Module\Editorial\Comment\Entity\AbstractComment;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Aurora\Module\Editorial\Comment\Repository\CommentReactionRepository;
use Aurora\Module\Editorial\Comment\Serializer\CommentSerializer;
use Aurora\Module\Editorial\Post\Entity\AbstractPost;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class CommentSerializerTest extends TestCase
{
    private CommentSerializer $serializer;

    protected function setUp(): void
    {
        $reactionRepo = $this->createStub(CommentReactionRepository::class);
        $reactionRepo->method('countByComment')->willReturn([]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key): string => "tr({$key})");

        $this->serializer = new CommentSerializer($reactionRepo, $translator);
    }

    public function testAdminSerializeIncludesPrivateFields(): void
    {
        $post = $this->makePost(id: 50, title: 'Hello world');
        $comment = $this->makeComment(
            id: 7,
            post: $post,
            authorName: 'Alice',
            authorEmail: 'alice@example.com',
            content: 'Nice post',
            status: CommentStatusEnum::Approved,
        );

        $payload = $this->serializer->serialize($comment);

        self::assertSame(7, $payload['id']);
        self::assertSame(50, $payload['postId']);
        self::assertSame('Hello world', $payload['postTitle']);
        self::assertSame('Alice', $payload['authorName']);
        self::assertSame('alice@example.com', $payload['authorEmail']);
        self::assertSame('Nice post', $payload['content']);
        self::assertSame('approved', $payload['status']);
        self::assertStringStartsWith('tr(', $payload['statusLabel'], 'label key is translated, exact value is i18n-dependent');
        self::assertNull($payload['parentId']);
        self::assertSame(0, $payload['replyCount']);
        self::assertSame(0, $payload['reactionCount']);
    }

    public function testAdminSerializeFallsBackToEmptyStringWhenPostHasNoTitle(): void
    {
        // Comment on a post whose first translation has a null title.
        $post = $this->makePost(id: 50, title: null);
        $comment = $this->makeComment(id: 1, post: $post);

        self::assertSame('', $this->serializer->serialize($comment)['postTitle']);
    }

    public function testAdminSerializeFallsBackToEmptyStringWhenPostHasNoTranslation(): void
    {
        // Edge case: post created without any translation yet.
        $post = new Post();
        (new ReflectionProperty(Post::class, 'id'))->setValue($post, 50);
        $comment = $this->makeComment(id: 1, post: $post);

        self::assertSame('', $this->serializer->serialize($comment)['postTitle']);
    }

    public function testAdminSerializeSumsReactionCountsByType(): void
    {
        $reactionRepo = $this->createStub(CommentReactionRepository::class);
        $reactionRepo->method('countByComment')->willReturn(['like' => 3, 'love' => 2, 'haha' => 1]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('translated');

        $serializer = new CommentSerializer($reactionRepo, $translator);

        $comment = $this->makeComment(id: 1, post: $this->makePost(id: 1, title: 'X'));

        self::assertSame(6, $serializer->serialize($comment)['reactionCount']);
    }

    public function testAdminSerializeExposesParentInfoWhenReply(): void
    {
        $post = $this->makePost(id: 50, title: 'P');
        $parent = $this->makeComment(id: 100, post: $post, authorName: 'Root');
        $reply = $this->makeComment(id: 101, post: $post);
        $reply->setParent($parent);

        $payload = $this->serializer->serialize($reply);

        self::assertSame(100, $payload['parentId']);
        self::assertSame('Root', $payload['parentAuthorName']);
    }

    public function testFrontSerializeExposesOnlyPublicFields(): void
    {
        // Email + status + reply/reaction counts must not leak to the
        // public payload.
        $comment = $this->makeComment(
            id: 1,
            post: $this->makePost(id: 1, title: 'X'),
            authorName: 'Bob',
            authorEmail: 'bob@example.com',
            content: 'public',
            status: CommentStatusEnum::Pending,
        );

        $payload = $this->serializer->serializeForFront($comment, []);

        self::assertSame(1, $payload['id']);
        self::assertSame('Bob', $payload['authorName']);
        self::assertSame('public', $payload['content']);
        self::assertArrayNotHasKey('authorEmail', $payload);
        self::assertArrayNotHasKey('status', $payload);
        self::assertArrayNotHasKey('statusLabel', $payload);
        self::assertArrayNotHasKey('replyCount', $payload);
    }

    public function testFrontSerializePropagatesReactionCountsFromMap(): void
    {
        // The reaction counts are pre-batched by the caller (one query
        // for the whole thread) and indexed by comment id.
        $comment = $this->makeComment(id: 42, post: $this->makePost(id: 1, title: 'X'));

        $payload = $this->serializer->serializeForFront($comment, [
            42 => ['like' => 5, 'love' => 1],
            99 => ['haha' => 9],
        ]);

        self::assertSame(['like' => 5, 'love' => 1], $payload['reactionCounts']);
    }

    public function testFrontSerializeEmptyMapMeansNoReactionsYet(): void
    {
        $comment = $this->makeComment(id: 42, post: $this->makePost(id: 1, title: 'X'));

        self::assertSame([], $this->serializer->serializeForFront($comment, [])['reactionCounts']);
    }

    public function testBuildFrontTreeSplitsRootsAndReplies(): void
    {
        $post = $this->makePost(id: 1, title: 'P');
        $rootA = $this->makeComment(id: 1, post: $post, authorName: 'A');
        $rootB = $this->makeComment(id: 2, post: $post, authorName: 'B');
        $replyToA = $this->makeComment(id: 3, post: $post, authorName: 'r-a');
        $replyToA->setParent($rootA);
        $deepReply = $this->makeComment(id: 4, post: $post, authorName: 'deep');
        $deepReply->setParent($replyToA); // grandchild of rootA → still attached to rootA

        $tree = $this->serializer->buildFrontTree([$rootA, $rootB, $replyToA, $deepReply], []);

        self::assertCount(2, $tree['roots']);
        self::assertSame('A', $tree['roots'][0]['authorName']);
        self::assertSame('B', $tree['roots'][1]['authorName']);
        // Both replies bucket under rootA's id (deepReply walks up the
        // parent chain to find its root).
        self::assertCount(2, $tree['replies'][1]);
        self::assertArrayNotHasKey(2, $tree['replies']);
    }

    public function testBuildFrontTreeIncludesEmojiMapForEveryReactionType(): void
    {
        $tree = $this->serializer->buildFrontTree([], []);

        $expectedKeys = array_map(static fn (ReactionTypeEnum $c): string => $c->value, ReactionTypeEnum::cases());
        sort($expectedKeys);
        $actualKeys = array_keys($tree['reactionEmojis']);
        sort($actualKeys);

        self::assertSame($expectedKeys, $actualKeys);
        // Every value is a non-empty emoji string.
        foreach ($tree['reactionEmojis'] as $emoji) {
            self::assertNotEmpty($emoji);
        }
    }

    public function testFindRootIdWalksThroughCommentMap(): void
    {
        $post = $this->makePost(id: 1, title: 'P');
        $root = $this->makeComment(id: 10, post: $post);
        $mid = $this->makeComment(id: 11, post: $post);
        $mid->setParent($root);
        $leaf = $this->makeComment(id: 12, post: $post);
        $leaf->setParent($mid);

        $map = [10 => $root, 11 => $mid, 12 => $leaf];

        self::assertSame(10, $this->serializer->findRootId($leaf, $map));
        self::assertSame(10, $this->serializer->findRootId($mid, $map));
        self::assertSame(10, $this->serializer->findRootId($root, $map));
    }

    // ── Fixture helpers ─────────────────────────────────────────────

    private function makeComment(
        int $id,
        Post $post,
        string $authorName = 'X',
        string $authorEmail = 'x@example.com',
        string $content = 'content',
        CommentStatusEnum $status = CommentStatusEnum::Pending,
    ): Comment {
        $comment = new Comment();
        (new ReflectionProperty(Comment::class, 'id'))->setValue($comment, $id);
        $comment->setPost($post);
        $comment->setAuthorName($authorName);
        $comment->setAuthorEmail($authorEmail);
        $comment->setContent($content);
        $comment->setStatus($status);

        (new ReflectionProperty(AbstractComment::class, 'createdAt'))->setValue($comment, new DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        return $comment;
    }

    private function makePost(int $id, ?string $title): Post
    {
        $post = new Post();
        (new ReflectionProperty(Post::class, 'id'))->setValue($post, $id);
        (new ReflectionProperty(AbstractPost::class, 'createdAt'))->setValue($post, new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        (new ReflectionProperty(AbstractPost::class, 'updatedAt'))->setValue($post, new DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        $translation = new PostTranslation();
        $translation->setPost($post);
        $translation->setLocale('fr');
        $translation->setTitle($title);
        $post->getTranslations()->set('fr', $translation);

        return $post;
    }
}

<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Comment\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Comment\Dto\CommentInput;
use Aurora\Module\Editorial\Comment\Entity\AbstractComment;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use Aurora\Module\Editorial\Comment\Manager\CommentManager;
use Aurora\Module\Editorial\Comment\Service\CommentNotificationService;
use Aurora\Module\Editorial\Post\Entity\AbstractPost;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class CommentManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private SettingRepository $settingRepository;
    private CommentNotificationService $notificationService;
    private CommentManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('CMT');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->notificationService = $this->createMock(CommentNotificationService::class);

        // SequenceGenerator is final — build the real thing on a stubbed
        // Connection so the next-counter call yields a deterministic 1.
        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $this->manager = new CommentManager(
            $this->entityManager,
            $this->settingRepository,
            $auditLogger,
            $this->notificationService,
            new SequenceGenerator($connection),
        );
    }

    public function testSubmitCreatesPendingCommentWhenModerationOn(): void
    {
        $this->settingRepository->method('getBoolean')->willReturn(true);

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');
        $this->notificationService->expects(self::once())->method('notifyPendingToAdmin');
        $this->notificationService->expects(self::never())->method('notifyApprovedToAuthor');

        $comment = $this->manager->submit($this->makePost(1), $this->makeInput(authorName: 'Alice', content: 'Hi'));

        self::assertSame(CommentStatusEnum::Pending, $comment->getStatus());
        self::assertSame('Alice', $comment->getAuthorName());
        self::assertSame('Hi', $comment->getContent());
        self::assertSame('CMT-000001', $comment->getReference());
        self::assertNull($comment->getParent());
    }

    public function testSubmitCreatesApprovedCommentAndNotifiesAuthorWhenModerationOff(): void
    {
        // When the platform setting disables moderation, comments are
        // auto-approved and the author receives the "approved" mail
        // straight away (instead of the admin receiving a "pending" one).
        $this->settingRepository->method('getBoolean')->willReturn(false);

        $this->notificationService->expects(self::once())->method('notifyApprovedToAuthor');
        $this->notificationService->expects(self::never())->method('notifyPendingToAdmin');

        $comment = $this->manager->submit($this->makePost(1), $this->makeInput());

        self::assertSame(CommentStatusEnum::Approved, $comment->getStatus());
    }

    public function testSubmitAttachesParentWhenProvided(): void
    {
        $this->settingRepository->method('getBoolean')->willReturn(true);

        $parent = $this->makeComment(id: 50);
        $reply = $this->manager->submit($this->makePost(1), $this->makeInput(), $parent);

        self::assertSame($parent, $reply->getParent());
    }

    public function testApproveTransitionsPendingToApprovedAndNotifiesAuthor(): void
    {
        $comment = $this->makeComment(id: 1);
        $comment->setStatus(CommentStatusEnum::Pending);

        $this->entityManager->expects(self::atLeastOnce())->method('flush');
        $this->notificationService->expects(self::once())->method('notifyApprovedToAuthor');

        $this->manager->approve($comment);

        self::assertSame(CommentStatusEnum::Approved, $comment->getStatus());
    }

    public function testApproveOnAlreadyApprovedDoesNotResendNotification(): void
    {
        // Idempotent admin clicks must not double-notify the author —
        // only the Pending → Approved transition triggers a mail.
        $comment = $this->makeComment(id: 1);
        $comment->setStatus(CommentStatusEnum::Approved);

        $this->notificationService->expects(self::never())->method('notifyApprovedToAuthor');

        $this->manager->approve($comment);
    }

    public function testSpamMarksAsSpamWithoutNotification(): void
    {
        $comment = $this->makeComment(id: 1);
        $comment->setStatus(CommentStatusEnum::Pending);

        $this->entityManager->expects(self::atLeastOnce())->method('flush');
        $this->notificationService->expects(self::never())->method('notifyApprovedToAuthor');

        $this->manager->spam($comment);

        self::assertSame(CommentStatusEnum::Spam, $comment->getStatus());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $comment = $this->makeComment(id: 1);

        $this->entityManager->expects(self::once())->method('remove')->with($comment);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($comment);
    }

    public function testAreCommentsEnabledRequiresBothPlatformSettingAndPostFlag(): void
    {
        // Platform flag AND per-post flag must both be true. Either one
        // off disables comments on that post.
        $post = $this->makePost(1);

        // Platform on, post on → enabled.
        $this->settingRepository->method('getBoolean')->willReturn(true);
        $post->setCommentsEnabled(true);
        self::assertTrue($this->manager->areCommentsEnabled($post));

        // Platform on, post off → disabled.
        $post->setCommentsEnabled(false);
        self::assertFalse($this->manager->areCommentsEnabled($post));
    }

    public function testAreCommentsEnabledFalseWhenPlatformDisabled(): void
    {
        // Platform setting trumps per-post: even an opt-in post is
        // disabled when the global toggle is off.
        $this->settingRepository->method('getBoolean')->willReturn(false);

        $post = $this->makePost(1);
        $post->setCommentsEnabled(true);

        self::assertFalse($this->manager->areCommentsEnabled($post));
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function makeInput(
        string $authorName = 'A',
        string $authorEmail = 'a@example.com',
        string $content = 'c',
        ?int $parentId = null,
    ): CommentInput {
        return new CommentInput(
            authorName: $authorName,
            authorEmail: $authorEmail,
            content: $content,
            parentId: $parentId,
        );
    }

    private function makePost(int $id): Post
    {
        $post = new Post();
        (new ReflectionProperty(Post::class, 'id'))->setValue($post, $id);
        $postType = new PostType();
        $postType->setSlug('article');
        $postType->setLabel('Article');
        $post->setPostType($postType);
        $post->setStatus(PostStatusEnum::Draft);

        $now = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        (new ReflectionProperty(AbstractPost::class, 'createdAt'))->setValue($post, $now);
        (new ReflectionProperty(AbstractPost::class, 'updatedAt'))->setValue($post, $now);

        return $post;
    }

    private function makeComment(int $id): Comment
    {
        $comment = new Comment();
        (new ReflectionProperty(Comment::class, 'id'))->setValue($comment, $id);
        $comment->setPost($this->makePost(1));
        $comment->setAuthorName('A');
        $comment->setAuthorEmail('a@example.com');
        $comment->setContent('c');
        (new ReflectionProperty(AbstractComment::class, 'createdAt'))->setValue($comment, new DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        return $comment;
    }
}

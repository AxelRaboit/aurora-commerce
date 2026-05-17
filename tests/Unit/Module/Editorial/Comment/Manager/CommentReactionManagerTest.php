<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Comment\Manager;

use Aurora\Module\Editorial\Comment\Entity\AbstractComment;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Entity\CommentReaction;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Aurora\Module\Editorial\Comment\Manager\CommentReactionManager;
use Aurora\Module\Editorial\Comment\Repository\CommentReactionRepository;
use Aurora\Module\Editorial\Post\Entity\AbstractPost;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
final class CommentReactionManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CommentReactionRepository $reactionRepository;
    private CommentReactionManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->reactionRepository = $this->createMock(CommentReactionRepository::class);
        $this->reactionRepository->method('countByComment')->willReturn([]);

        $this->manager = new CommentReactionManager($this->entityManager, $this->reactionRepository);
    }

    public function testToggleCreatesNewReactionWhenNoneExists(): void
    {
        // First reaction from this fingerprint on this comment → persist
        // a new row with the chosen type.
        $comment = $this->makeComment(id: 42);
        $this->reactionRepository->method('findByCommentAndFingerprint')->willReturn(null);

        $this->entityManager->expects(self::once())->method('persist')
            ->with(self::callback(static function (CommentReaction $reaction) use ($comment): bool {
                self::assertSame($comment, $reaction->getComment());
                self::assertSame(ReactionTypeEnum::Like, $reaction->getType());
                self::assertSame('fp-1', $reaction->getFingerprint());

                return true;
            }));
        $this->entityManager->expects(self::atLeastOnce())->method('flush');
        $this->entityManager->expects(self::never())->method('remove');

        $this->manager->toggle($comment, ReactionTypeEnum::Like, 'fp-1');
    }

    public function testToggleRemovesReactionWhenSameTypeIsRepeated(): void
    {
        // User clicks the same emoji twice → un-reaction. Remove the row.
        $comment = $this->makeComment(id: 42);
        $existing = $this->makeReaction(comment: $comment, type: ReactionTypeEnum::Love, fingerprint: 'fp-1');
        $this->reactionRepository->method('findByCommentAndFingerprint')->willReturn($existing);

        $this->entityManager->expects(self::once())->method('remove')->with($existing);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');
        $this->entityManager->expects(self::never())->method('persist');

        $this->manager->toggle($comment, ReactionTypeEnum::Love, 'fp-1');
    }

    public function testToggleSwitchesTypeWhenDifferentTypeAlreadyExists(): void
    {
        // User changes their mind: was Like, now Haha → update the
        // existing row's type rather than create a second one (each
        // fingerprint can only hold ONE active reaction per comment).
        $comment = $this->makeComment(id: 42);
        $existing = $this->makeReaction(comment: $comment, type: ReactionTypeEnum::Like, fingerprint: 'fp-1');
        $this->reactionRepository->method('findByCommentAndFingerprint')->willReturn($existing);

        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('remove');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->toggle($comment, ReactionTypeEnum::Haha, 'fp-1');

        self::assertSame(ReactionTypeEnum::Haha, $existing->getType());
    }

    public function testToggleReturnsCountByCommentAfterPersisting(): void
    {
        // Toggle returns a fresh count map so the front can update the
        // UI without a separate fetch. The counts come from the repo
        // after the flush.
        $reactionRepository = $this->createMock(CommentReactionRepository::class);
        $reactionRepository->method('findByCommentAndFingerprint')->willReturn(null);
        $reactionRepository->expects(self::once())
            ->method('countByComment')
            ->with(42)
            ->willReturn(['like' => 3, 'love' => 1]);

        $manager = new CommentReactionManager($this->entityManager, $reactionRepository);

        $counts = $manager->toggle($this->makeComment(42), ReactionTypeEnum::Like, 'fp');

        self::assertSame(['like' => 3, 'love' => 1], $counts);
    }

    public function testGenerateFingerprintHashesIpAndUserAgent(): void
    {
        // Anonymity-by-pseudonym: the fingerprint should never be the
        // raw IP. Hash ensures the DB never stores PII-recoverable
        // values and lets two reactions from same client be
        // deduplicated without revealing identity.
        $request = Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.5', 'HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $fingerprint = $this->manager->generateFingerprint($request);

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $fingerprint, 'fingerprint is a sha256 hex (64 chars)');
        self::assertStringNotContainsString('203.0.113.5', $fingerprint, 'raw IP must not survive in the fingerprint');
    }

    public function testGenerateFingerprintIsStableForSameClient(): void
    {
        // Same IP + UA always yields the same fingerprint — enables
        // dedup (one reaction per client per comment).
        $request1 = Request::create('/', server: ['REMOTE_ADDR' => '1.1.1.1', 'HTTP_USER_AGENT' => 'UA-A']);
        $request2 = Request::create('/', server: ['REMOTE_ADDR' => '1.1.1.1', 'HTTP_USER_AGENT' => 'UA-A']);

        self::assertSame(
            $this->manager->generateFingerprint($request1),
            $this->manager->generateFingerprint($request2),
        );
    }

    public function testGenerateFingerprintDiffersForDifferentUserAgent(): void
    {
        // Same IP, different UA (e.g. mobile vs desktop on same wifi) →
        // distinct fingerprints, so each device can react independently.
        $mobile = Request::create('/', server: ['REMOTE_ADDR' => '1.1.1.1', 'HTTP_USER_AGENT' => 'Mobile']);
        $desktop = Request::create('/', server: ['REMOTE_ADDR' => '1.1.1.1', 'HTTP_USER_AGENT' => 'Desktop']);

        self::assertNotSame(
            $this->manager->generateFingerprint($mobile),
            $this->manager->generateFingerprint($desktop),
        );
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function makeComment(int $id): Comment
    {
        $post = new Post();
        $postType = new PostType();
        $postType->setSlug('a');
        $postType->setLabel('A');
        $post->setPostType($postType);
        $post->setStatus(PostStatusEnum::Draft);
        $now = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        (new ReflectionProperty(AbstractPost::class, 'createdAt'))->setValue($post, $now);
        (new ReflectionProperty(AbstractPost::class, 'updatedAt'))->setValue($post, $now);

        $comment = new Comment();
        (new ReflectionProperty(Comment::class, 'id'))->setValue($comment, $id);
        $comment->setPost($post);
        $comment->setAuthorName('A');
        $comment->setAuthorEmail('a@example.com');
        $comment->setContent('c');
        (new ReflectionProperty(AbstractComment::class, 'createdAt'))->setValue($comment, $now);

        return $comment;
    }

    private function makeReaction(Comment $comment, ReactionTypeEnum $type, string $fingerprint): CommentReaction
    {
        $reaction = new CommentReaction();
        $reaction->setComment($comment);
        $reaction->setType($type);
        $reaction->setFingerprint($fingerprint);

        return $reaction;
    }
}

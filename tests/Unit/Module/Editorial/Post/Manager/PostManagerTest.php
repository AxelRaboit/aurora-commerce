<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Editorial\Post\Dto\PostInput;
use Aurora\Module\Editorial\Post\Dto\PostTranslationInput;
use Aurora\Module\Editorial\Post\Entity\AbstractPost;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Manager\PostManager;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostRevisionRepository;
use Aurora\Module\Editorial\Post\Repository\PostSlugHistoryRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class PostManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PostTypeRepository $postTypeRepository;
    private TaxonomyTermRepository $termRepository;
    private MediaRepository $mediaRepository;
    private PostRevisionRepository $revisionRepository;
    private PostSlugHistoryRepository $slugHistoryRepository;
    private SettingRepository $settingRepository;
    private Security $security;
    private PostTextExtractor $textExtractor;
    private SequenceGenerator $sequenceGenerator;
    private PostRepository $postRepository;
    private PostManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->postTypeRepository = $this->createMock(PostTypeRepository::class);
        $this->termRepository = $this->createMock(TaxonomyTermRepository::class);
        $this->mediaRepository = $this->createMock(MediaRepository::class);
        $this->revisionRepository = $this->createMock(PostRevisionRepository::class);
        $this->slugHistoryRepository = $this->createMock(PostSlugHistoryRepository::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('POST');
        $this->settingRepository->method('get')->willReturn('0');
        $this->security = $this->createMock(Security::class);
        // PostTextExtractor is `final readonly` (can't be stubbed) but
        // has no constructor deps — use the real thing. It just extracts
        // text from block payloads; tests don't care about the value,
        // only that it doesn't crash.
        $this->textExtractor = new PostTextExtractor();
        // SequenceGenerator is `final` — build the real thing on top of
        // a stubbed Connection whose `executeQuery()` returns a Result
        // stub yielding the next sequence value.
        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);
        $this->sequenceGenerator = new SequenceGenerator($connection);
        $this->postRepository = $this->createMock(PostRepository::class);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('translated');

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $this->entityManager->method('getConnection')->willReturn($this->createStub(Connection::class));

        $auditLogger = new AuditLogger(
            $this->entityManager,
            $this->security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->manager = new PostManager(
            $this->entityManager,
            $this->postTypeRepository,
            $this->termRepository,
            $this->mediaRepository,
            $this->revisionRepository,
            $this->slugHistoryRepository,
            $this->settingRepository,
            new AsciiSlugger(),
            $this->security,
            $this->textExtractor,
            $translator,
            $auditLogger,
            $this->sequenceGenerator,
            $this->postRepository,
        );
    }

    public function testCreateSetsAuthorFromSecurityAndAssignsReference(): void
    {
        $author = $this->makeUser(99);
        $this->security->method('getUser')->willReturn($author);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $input = $this->makeInput(postTypeId: 1, translations: ['fr' => $this->makeTranslationInput(title: 'Hello')]);

        $post = $this->manager->create($input);

        self::assertSame($author, $post->getAuthor());
        self::assertSame('POST-000001', $post->getReference(), 'reference is prefix + zero-padded sequence number');
    }

    public function testCreateLeavesAuthorNullWhenNoUserAuthenticated(): void
    {
        // CLI / cron context: security has no user. Post is still created
        // but has no author (the controller would refuse anonymous POSTs
        // anyway; this just keeps the manager robust outside that path).
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->manager->create($this->makeInput(postTypeId: 1));

        self::assertNull($post->getAuthor());
    }

    public function testApplyInputThrowsWhenPostTypeMissing(): void
    {
        // PostType is a hard dependency — a deleted-then-referenced id
        // must fail loudly rather than persist a post in limbo.
        $this->postTypeRepository->method('find')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->create($this->makeInput(postTypeId: 999));
    }

    public function testApplyInputPublishedStatusSetsPublishedAtIfNotAlreadySet(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->manager->create($this->makeInput(postTypeId: 1, status: 'published'));

        self::assertInstanceOf(DateTimeImmutable::class, $post->getPublishedAt());
    }

    public function testApplyInputPublishedStatusKeepsExistingPublishedAt(): void
    {
        // Already-published post is re-saved → publishedAt must stay the
        // original publication date, not be overwritten with now().
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $originalPublishedAt = new DateTimeImmutable('2024-06-15T10:00:00+00:00');
        $post = $this->makePost();
        $post->setPublishedAt($originalPublishedAt);

        $this->manager->update($post, $this->makeInput(postTypeId: 1, status: 'published'));

        self::assertSame($originalPublishedAt, $post->getPublishedAt());
    }

    public function testApplyInputScheduledStatusSetsScheduledAt(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->makePost();
        $this->manager->update($post, $this->makeInput(
            postTypeId: 1,
            status: 'scheduled',
            scheduledAt: '2026-12-25T09:00:00+00:00',
        ));

        self::assertEquals(new DateTimeImmutable('2026-12-25T09:00:00+00:00'), $post->getScheduledAt());
    }

    public function testApplyInputNonScheduledStatusClearsScheduledAt(): void
    {
        // User saves a scheduled post but changes status to draft → the
        // scheduledAt date must be cleared (otherwise it could re-publish
        // unexpectedly via cron).
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->makePost();
        $post->setScheduledAt(new DateTimeImmutable('2026-12-25T09:00:00+00:00'));

        $this->manager->update($post, $this->makeInput(postTypeId: 1, status: 'draft'));

        self::assertNull($post->getScheduledAt());
    }

    public function testSyncTermsAddsMissingAndRemovesObsolete(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->makePost();
        $existingKept = $this->makeTerm(10);
        $existingRemoved = $this->makeTerm(20);
        $post->addTerm($existingKept);
        $post->addTerm($existingRemoved);

        // Input keeps 10, drops 20, adds 30.
        $newTerm = $this->makeTerm(30);
        $this->termRepository->method('findBy')->willReturn([$newTerm]);

        $this->manager->update($post, $this->makeInput(postTypeId: 1, termIds: [10, 30]));

        $ids = $post->getTerms()->map(fn (TaxonomyTerm $t): ?int => $t->getId())->toArray();
        sort($ids);
        self::assertSame([10, 30], $ids);
    }

    public function testSyncRelatedPostsFiltersSelfReference(): void
    {
        // A post linking to itself = link loop / spam. Quietly dropped.
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->makePost(id: 42);
        $other = $this->makePost(id: 100);

        $this->postRepository->method('findBy')->willReturn([$other]);

        $this->manager->update($post, $this->makeInput(postTypeId: 1, relatedPostIds: [42, 100]));

        $ids = $post->getRelatedPosts()->map(fn (Post $p): ?int => $p->getId())->toArray();
        self::assertSame([100], $ids);
    }

    public function testDeleteSoftDeletesPostByStampingDeletedAt(): void
    {
        $post = $this->makePost();
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($post);

        self::assertInstanceOf(DateTimeImmutable::class, $post->getDeletedAt());
        self::assertTrue($post->isTrashed());
    }

    public function testDeleteIsIdempotentOnAlreadyTrashedPost(): void
    {
        $post = $this->makePost();
        $originalDeletedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $post->setDeletedAt($originalDeletedAt);

        $this->entityManager->expects(self::never())->method('flush');

        $this->manager->delete($post);

        self::assertSame($originalDeletedAt, $post->getDeletedAt(), 'second delete on already-trashed post is a no-op (timestamp untouched)');
    }

    public function testRestoreClearsDeletedAt(): void
    {
        $post = $this->makePost();
        $post->setDeletedAt(new DateTimeImmutable());
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->restore($post);

        self::assertNull($post->getDeletedAt());
        self::assertFalse($post->isTrashed());
    }

    public function testForceDeleteRemovesEntity(): void
    {
        $post = $this->makePost();
        $this->entityManager->expects(self::once())->method('remove')->with($post);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->forceDelete($post);
    }

    public function testEmptyTrashReturnsZeroOnEmptyTrash(): void
    {
        $this->postRepository->method('findAllTrashed')->willReturn([]);
        $this->entityManager->expects(self::never())->method('remove');
        $this->entityManager->expects(self::never())->method('flush');

        self::assertSame(0, $this->manager->emptyTrash());
    }

    public function testEmptyTrashRemovesEveryTrashedPostAndReturnsCount(): void
    {
        $trashed = [$this->makePost(id: 1), $this->makePost(id: 2), $this->makePost(id: 3)];
        $this->postRepository->method('findAllTrashed')->willReturn($trashed);

        $this->entityManager->expects(self::exactly(3))->method('remove');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        self::assertSame(3, $this->manager->emptyTrash());
    }

    public function testDemoteIfNotPublishableLeavesNonPublishedInputUntouched(): void
    {
        // Only published-status inputs are subject to the demotion
        // check — drafts/scheduled/archived pass through unchanged.
        $input = $this->makeInput(postTypeId: 1, status: 'draft');
        $this->security->expects(self::never())->method('isGranted');

        self::assertSame($input, $this->manager->demoteIfNotPublishable($input));
    }

    public function testDemoteIfNotPublishableDowngradesToPendingReviewWhenUnauthorized(): void
    {
        // Non-admin tries to create a published post → silently downgraded
        // to PendingReview rather than rejected. Avoids a 403 in the UI
        // while still requiring an admin to approve before the post goes
        // live.
        $this->security->method('isGranted')->willReturn(false);

        $input = $this->makeInput(postTypeId: 1, status: 'published');
        $result = $this->manager->demoteIfNotPublishable($input);

        self::assertSame(PostStatusEnum::PendingReview->value, $result->getStatus());
    }

    public function testDemoteIfNotPublishablePassesThroughWhenAdmin(): void
    {
        $this->security->method('isGranted')->willReturn(true);

        $input = $this->makeInput(postTypeId: 1, status: 'published');
        $result = $this->manager->demoteIfNotPublishable($input);

        self::assertSame(PostStatusEnum::Published->value, $result->getStatus());
    }

    public function testApplyTranslationRecordsSlugHistoryOnChange(): void
    {
        // Slug change → old slug archived as a redirect entry so any
        // bookmarked URL still resolves. Critical for SEO.
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->makePost();
        $translation = $post->translate('fr');
        $translation->setSlug('old-slug');

        $this->slugHistoryRepository->expects(self::once())
            ->method('recordIfNew')
            ->with($post, 'fr', 'old-slug');

        $this->manager->update($post, $this->makeInput(
            postTypeId: 1,
            translations: ['fr' => $this->makeTranslationInput(title: 'Hello', slug: 'new-slug')],
        ));

        self::assertSame('new-slug', $post->getTranslation('fr')?->getSlug());
    }

    public function testApplyTranslationRemovesSlugFromHistoryIfNewSlugWasArchived(): void
    {
        // Switching back to a previously-used slug → drop the now-stale
        // history row so we don't redirect a slug to itself (infinite
        // loop).
        $this->security->method('getUser')->willReturn(null);
        $this->postTypeRepository->method('find')->willReturn($this->makePostType(1));

        $post = $this->makePost();
        $translation = $post->translate('fr');
        $translation->setSlug('current');

        $this->slugHistoryRepository->expects(self::once())
            ->method('removeByLocaleAndSlug')
            ->with('fr', 'previous');

        $this->manager->update($post, $this->makeInput(
            postTypeId: 1,
            translations: ['fr' => $this->makeTranslationInput(title: 'X', slug: 'previous')],
        ));
    }

    // ── Fixture helpers ─────────────────────────────────────────────

    /**
     * @param array<int>                          $termIds
     * @param array<int>                          $relatedPostIds
     * @param array<string, PostTranslationInput> $translations
     */
    private function makeInput(
        int $postTypeId,
        string $status = 'draft',
        ?string $scheduledAt = null,
        array $termIds = [],
        array $relatedPostIds = [],
        array $translations = [],
    ): PostInput {
        return new PostInput(
            postTypeId: $postTypeId,
            status: $status,
            featuredMediaId: null,
            termIds: $termIds,
            translations: $translations,
            relatedPostIds: $relatedPostIds,
            scheduledAt: $scheduledAt,
        );
    }

    private function makeTranslationInput(
        ?string $title = null,
        ?string $slug = null,
    ): PostTranslationInput {
        return new PostTranslationInput(
            title: $title,
            slug: $slug,
            blocks: [],
            metaTitle: null,
            metaDescription: null,
            customFields: [],
            ogImageMediaId: null,
            canonicalUrl: null,
            noindex: false,
            focusKeyword: null,
            jsonLd: null,
        );
    }

    private function makePost(int $id = 1): Post
    {
        $post = new Post();
        (new ReflectionProperty(Post::class, 'id'))->setValue($post, $id);
        $post->setPostType($this->makePostType(1));
        $post->setStatus(PostStatusEnum::Draft);

        $now = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        (new ReflectionProperty(AbstractPost::class, 'createdAt'))->setValue($post, $now);
        (new ReflectionProperty(AbstractPost::class, 'updatedAt'))->setValue($post, $now);

        return $post;
    }

    private function makePostType(int $id): PostType
    {
        $postType = new PostType();
        (new ReflectionProperty(PostType::class, 'id'))->setValue($postType, $id);
        $postType->setSlug('article');
        $postType->setLabel('Article');

        return $postType;
    }

    private function makeTerm(int $id): TaxonomyTerm
    {
        $term = new TaxonomyTerm();
        (new ReflectionProperty(TaxonomyTerm::class, 'id'))->setValue($term, $id);

        return $term;
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);
        // AuditLogger reads getEmail() + getName() on the actor — both
        // must be set so a security-user-bearing flow doesn't blow up
        // on lazy-init.
        $user->setEmail(sprintf('user-%d@test', $id));
        $user->setName(sprintf('User %d', $id));

        return $user;
    }
}

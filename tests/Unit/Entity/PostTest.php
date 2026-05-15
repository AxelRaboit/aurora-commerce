<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Post())->getId());
    }

    public function testDefaultStatusIsDraft(): void
    {
        self::assertSame(PostStatusEnum::Draft, (new Post())->getStatus());
    }

    public function testIsPublishedOnlyWhenStatusIsPublished(): void
    {
        $post = new Post();

        self::assertFalse($post->isPublished());

        $post->setStatus(PostStatusEnum::Published);
        self::assertTrue($post->isPublished());

        $post->setStatus(PostStatusEnum::Archived);
        self::assertFalse($post->isPublished());
    }

    public function testIsTrashedReflectsDeletedAt(): void
    {
        $post = new Post();

        self::assertFalse($post->isTrashed());

        $post->setDeletedAt(new DateTimeImmutable());
        self::assertTrue($post->isTrashed());

        $post->setDeletedAt(null);
        self::assertFalse($post->isTrashed());
    }

    public function testTermsCollectionInitialized(): void
    {
        self::assertCount(0, (new Post())->getTerms());
    }

    public function testAddTermAndRemoveTerm(): void
    {
        $post = new Post();
        $term = $this->createStub(TaxonomyTermInterface::class);

        $post->addTerm($term);
        self::assertCount(1, $post->getTerms());

        $post->addTerm($term);
        self::assertCount(1, $post->getTerms(), 'duplicate is ignored');

        $post->removeTerm($term);
        self::assertCount(0, $post->getTerms());
    }

    public function testRelatedPostsCollectionInitialized(): void
    {
        self::assertCount(0, (new Post())->getRelatedPosts());
    }

    public function testAddRelatedPostAndRemove(): void
    {
        $post = new Post();
        $related = new Post();

        $post->addRelatedPost($related);
        self::assertCount(1, $post->getRelatedPosts());

        $post->addRelatedPost($related);
        self::assertCount(1, $post->getRelatedPosts(), 'duplicate is ignored');

        $post->removeRelatedPost($related);
        self::assertCount(0, $post->getRelatedPosts());
    }

    public function testAddRelatedPostIgnoresSelf(): void
    {
        $post = new Post();

        $post->addRelatedPost($post);
        self::assertCount(0, $post->getRelatedPosts(), 'self-relation is ignored');
    }

    public function testCommentsEnabledDefaultAndSetter(): void
    {
        $post = new Post();

        self::assertTrue($post->isCommentsEnabled());

        $post->setCommentsEnabled(false);
        self::assertFalse($post->isCommentsEnabled());
    }
}

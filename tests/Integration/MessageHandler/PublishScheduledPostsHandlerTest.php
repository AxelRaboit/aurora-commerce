<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\MessageHandler;

use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Message\PublishScheduledPostsMessage;
use Aurora\Module\Editorial\Post\MessageHandler\PublishScheduledPostsHandler;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\PostType\Repository\PostTypeRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class PublishScheduledPostsHandlerTest extends IntegrationTestCase
{
    public function testDuePostsAreFlippedToPublished(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $postType = $container->get(PostTypeRepository::class)->findOneBy([]);

        $duePost = (new Post())
            ->setPostType($postType)
            ->setStatus(PostStatusEnum::Scheduled)
            ->setScheduledAt(new DateTimeImmutable('-1 hour'));

        $futurePost = (new Post())
            ->setPostType($postType)
            ->setStatus(PostStatusEnum::Scheduled)
            ->setScheduledAt(new DateTimeImmutable('+1 hour'));

        $entityManager->persist($duePost);
        $entityManager->persist($futurePost);
        $entityManager->flush();

        $dueId = $duePost->getId();
        $futureId = $futurePost->getId();

        ($container->get(PublishScheduledPostsHandler::class))(new PublishScheduledPostsMessage());

        $entityManager->clear();
        $repository = $container->get(PostRepository::class);

        $refreshedDue = $repository->find($dueId);
        self::assertSame(PostStatusEnum::Published, $refreshedDue->getStatus());
        self::assertNotNull($refreshedDue->getPublishedAt());
        self::assertNull($refreshedDue->getScheduledAt());

        $refreshedFuture = $repository->find($futureId);
        self::assertSame(PostStatusEnum::Scheduled, $refreshedFuture->getStatus());
        self::assertNotNull($refreshedFuture->getScheduledAt());
    }
}

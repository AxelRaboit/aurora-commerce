<?php

declare(strict_types=1);

namespace App\Tests\Integration\MessageHandler;

use App\Module\Editorial\Post\Entity\Post;
use App\Module\Editorial\Post\Enum\PostStatusEnum;
use App\Module\Editorial\Post\Message\PublishScheduledPostsMessage;
use App\Module\Editorial\Post\MessageHandler\PublishScheduledPostsHandler;
use App\Module\Editorial\Post\Repository\PostRepository;
use App\Module\Editorial\Post\Repository\PostTypeRepository;
use App\Tests\Integration\IntegrationTestCase;
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

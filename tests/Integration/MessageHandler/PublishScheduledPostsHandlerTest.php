<?php

declare(strict_types=1);

namespace App\Tests\Integration\MessageHandler;

use App\Entity\Post;
use App\Enum\PostStatusEnum;
use App\Message\PublishScheduledPostsMessage;
use App\MessageHandler\PublishScheduledPostsHandler;
use App\Repository\Post\PostRepository;
use App\Repository\Post\PostTypeRepository;
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

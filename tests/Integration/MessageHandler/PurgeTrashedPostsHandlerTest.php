<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\MessageHandler;

use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Message\PurgeTrashedPostsMessage;
use Aurora\Module\Editorial\Post\MessageHandler\PurgeTrashedPostsHandler;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class PurgeTrashedPostsHandlerTest extends IntegrationTestCase
{
    public function testPurgesPostsOlderThanConfiguredDays(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $postType = $container->get(PostTypeRepository::class)->findOneBy([]);

        $container->get(SettingRepository::class)->set('trash_auto_purge_days', '7');

        $oldTrash = (new Post())
            ->setPostType($postType)
            ->setStatus(PostStatusEnum::Draft)
            ->setDeletedAt(new DateTimeImmutable('-30 days'));

        $recentTrash = (new Post())
            ->setPostType($postType)
            ->setStatus(PostStatusEnum::Draft)
            ->setDeletedAt(new DateTimeImmutable('-1 day'));

        $entityManager->persist($oldTrash);
        $entityManager->persist($recentTrash);
        $entityManager->flush();

        $oldId = $oldTrash->getId();
        $recentId = $recentTrash->getId();

        ($container->get(PurgeTrashedPostsHandler::class))(new PurgeTrashedPostsMessage());

        $entityManager->clear();
        $repository = $container->get(PostRepository::class);
        self::assertNull($repository->find($oldId));
        self::assertNotNull($repository->find($recentId));
    }

    public function testDisabledWhenZero(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $postType = $container->get(PostTypeRepository::class)->findOneBy([]);

        $container->get(SettingRepository::class)->set('trash_auto_purge_days', '0');

        $veryOldTrash = (new Post())
            ->setPostType($postType)
            ->setStatus(PostStatusEnum::Draft)
            ->setDeletedAt(new DateTimeImmutable('-365 days'));

        $entityManager->persist($veryOldTrash);
        $entityManager->flush();

        $id = $veryOldTrash->getId();
        ($container->get(PurgeTrashedPostsHandler::class))(new PurgeTrashedPostsMessage());

        $entityManager->clear();
        self::assertNotNull($container->get(PostRepository::class)->find($id));
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\PostStatusEnum;
use App\Repository\Media\MediaRepository;
use App\Repository\Menu\MenuRepository;
use App\Repository\Post\PostRepository;
use App\Repository\Post\PostTypeRepository;
use App\Repository\User\UserRepository;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class AdminStatsService
{
    public function __construct(
        private PostRepository $postRepository,
        private PostTypeRepository $postTypeRepository,
        private MediaRepository $mediaRepository,
        private MenuRepository $menuRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'posts' => $this->getPostStats(),
            'media' => $this->getMediaStats(),
            'menus' => [
                'total' => $this->menuRepository->count([]),
            ],
            'users' => [
                'total' => $this->userRepository->count([]),
            ],
            'postsByMonth' => $this->getPostsByMonth(),
            'recentPosts' => $this->getRecentPosts(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPostStats(): array
    {
        $byType = [];
        foreach ($this->postTypeRepository->findAll() as $type) {
            $byType[] = [
                'slug' => $type->getSlug(),
                'label' => $type->getLabel(),
                'count' => $this->postRepository->count(['postType' => $type]),
            ];
        }

        return [
            'total' => $this->postRepository->count([]),
            'published' => $this->postRepository->count(['status' => PostStatusEnum::Published, 'deletedAt' => null]),
            'draft' => $this->postRepository->count(['status' => PostStatusEnum::Draft, 'deletedAt' => null]),
            'pendingReview' => $this->postRepository->count(['status' => PostStatusEnum::PendingReview, 'deletedAt' => null]),
            'scheduled' => $this->postRepository->count(['status' => PostStatusEnum::Scheduled, 'deletedAt' => null]),
            'archived' => $this->postRepository->count(['status' => PostStatusEnum::Archived, 'deletedAt' => null]),
            'trashed' => $this->postRepository->countTrashed(),
            'byType' => $byType,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMediaStats(): array
    {
        return [
            'total' => $this->mediaRepository->count([]),
            'totalSize' => $this->mediaRepository->getTotalStorageSize(),
        ];
    }

    /**
     * @return array<int, array{month: string, count: int}>
     */
    private function getPostsByMonth(): array
    {
        $since = (new DateTimeImmutable('-5 months'))->modify('first day of this month')->setTime(0, 0);
        $monthCountMap = $this->postRepository->countByMonthSince($since);

        $result = [];
        for ($monthOffset = 5; $monthOffset >= 0; --$monthOffset) {
            $monthKey = (new DateTimeImmutable(sprintf('-%d months', $monthOffset)))->format('Y-m');
            $result[] = ['month' => $monthKey, 'count' => $monthCountMap[$monthKey] ?? 0];
        }

        return $result;
    }

    /**
     * @return array<int, array{id: int, title: string, status: string, updatedAt: string, postType: string}>
     */
    private function getRecentPosts(): array
    {
        $result = [];
        foreach ($this->postRepository->findRecent(5) as $post) {
            $firstTranslation = $post->getTranslations()->first() ?: null;
            $result[] = [
                'id' => $post->getId(),
                'title' => $firstTranslation ? $firstTranslation->getTitle() : '(sans titre)',
                'status' => $post->getStatus()->value,
                'updatedAt' => $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
                'postType' => $post->getPostType()->getLabel(),
            ];
        }

        return $result;
    }
}

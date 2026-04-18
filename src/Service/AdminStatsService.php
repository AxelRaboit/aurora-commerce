<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Media;
use App\Entity\Post;
use App\Repository\MediaRepository;
use App\Repository\MenuRepository;
use App\Repository\PostRepository;
use App\Repository\PostTypeRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AdminStatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
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
        $total = $this->postRepository->count([]);
        $published = $this->postRepository->count(['status' => Post::STATUS_PUBLISHED]);
        $draft = $this->postRepository->count(['status' => Post::STATUS_DRAFT]);
        $trash = $this->postRepository->count(['status' => Post::STATUS_TRASH]);

        $byType = [];
        foreach ($this->postTypeRepository->findAll() as $type) {
            $byType[] = [
                'slug' => $type->getSlug(),
                'label' => $type->getLabel(),
                'count' => $this->postRepository->count(['postType' => $type]),
            ];
        }

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $draft,
            'trash' => $trash,
            'byType' => $byType,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMediaStats(): array
    {
        $total = $this->mediaRepository->count([]);

        $totalSize = (int) $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(m.size), 0)')
            ->from(Media::class, 'm')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'totalSize' => $totalSize,
        ];
    }

    /**
     * @return array<int, array{month: string, count: int}>
     */
    private function getPostsByMonth(): array
    {
        $sqlQuery = <<<'SQL'
                SELECT TO_CHAR(created_at, 'YYYY-MM') AS month, COUNT(*) AS count
                FROM posts
                WHERE created_at >= :since
                GROUP BY month
                ORDER BY month ASC
            SQL;

        $since = (new DateTimeImmutable('-5 months'))->modify('first day of this month')->setTime(0, 0);
        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sqlQuery, ['since' => $since->format('Y-m-d H:i:s')]);

        $monthCountMap = [];
        foreach ($rows as $row) {
            $monthCountMap[$row['month']] = (int) $row['count'];
        }

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
        $posts = $this->postRepository->createQueryBuilder('p')
            ->leftJoin('p.postType', 'pt')
            ->leftJoin('p.translations', 't')
            ->addSelect('pt', 't')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($posts as $post) {
            $firstTranslation = $post->getTranslations()->first() ?: null;
            $result[] = [
                'id' => $post->getId(),
                'title' => $firstTranslation ? $firstTranslation->getTitle() : '(sans titre)',
                'status' => $post->getStatus(),
                'updatedAt' => $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
                'postType' => $post->getPostType()?->getLabel() ?? '',
            ];
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\Post;
use App\Entity\TaxonomyTerm;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\MediaRepository;
use App\Repository\PostRepository;
use App\Repository\TaxonomyTermRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/search', name: 'admin_search')]
#[IsGranted(UserRoleEnum::Admin->value)]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly TaxonomyTermRepository $termRepository,
        private readonly MediaRepository $mediaRepository,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = mb_trim((string) $request->query->get('q', ''));
        if ('' === $query) {
            return $this->json(['success' => true, 'posts' => [], 'terms' => [], 'media' => []]);
        }

        $defaultLocale = (string) ($this->getParameter('kernel.default_locale') ?? 'fr');

        // ── Posts (full-text via tsvector) ──────────────────────────────────
        $postIds = $this->postRepository->fullTextPostIds($query, 10);
        $posts = [] !== $postIds ? $this->postRepository->findByIds($postIds) : [];
        $orderById = array_flip($postIds);
        usort($posts, static fn (Post $a, Post $b): int => ($orderById[$a->getId()] ?? PHP_INT_MAX) <=> ($orderById[$b->getId()] ?? PHP_INT_MAX));

        $postsSerialized = array_map(
            static fn (Post $post): array => [
                'id' => $post->getId(),
                'title' => $post->getTranslation('fr')?->getTitle() ?? ($post->getTranslations()->first() ?: null)?->getTitle(),
                'status' => $post->getStatus()->value,
                'postType' => $post->getPostType()->getLabel(),
                'trashed' => $post->isTrashed(),
                'snippet' => self::buildSnippet(
                    $post->getTranslation('fr')?->getSearchContent()
                    ?? ($post->getTranslations()->first() ?: null)?->getSearchContent(),
                    $query,
                ),
            ],
            $posts,
        );

        // ── Terms ───────────────────────────────────────────────────────────
        $termsSerialized = array_map(
            fn (TaxonomyTerm $term): array => [
                'id' => $term->getId(),
                'name' => $term->getTranslation($defaultLocale)?->getName()
                    ?? ($term->getTranslations()->first() ?: null)?->getName(),
                'taxonomy' => $term->getTaxonomy()->getSlug(),
            ],
            $this->termRepository->searchByName($query, 10),
        );

        // ── Media ───────────────────────────────────────────────────────────
        $mediaSerialized = array_map(
            static fn (Media $media): array => [
                'id' => $media->getId(),
                'name' => $media->getOriginalName(),
                'mimeType' => $media->getMimeType(),
                'alt' => $media->getAlt(),
            ],
            $this->mediaRepository->searchByName($query, 10),
        );

        return $this->json([
            'success' => true,
            'posts' => $postsSerialized,
            'terms' => $termsSerialized,
            'media' => $mediaSerialized,
        ]);
    }

    private static function buildSnippet(?string $content, string $query, int $radius = 60): string
    {
        if (null === $content || '' === $content) {
            return '';
        }

        $lowerContent = mb_strtolower($content);
        $lowerQuery = mb_strtolower(preg_replace('/["\-+]/', '', $query) ?? '');
        $tokens = array_values(array_filter(explode(' ', $lowerQuery), static fn (string $token): bool => '' !== $token));

        foreach ($tokens as $token) {
            $position = mb_strpos($lowerContent, $token);
            if (false !== $position) {
                $start = max(0, $position - $radius);
                $length = mb_strlen($token) + $radius * 2;
                $snippet = mb_substr($content, $start, $length);
                if ($start > 0) {
                    $snippet = '…'.$snippet;
                }

                if ($start + $length < mb_strlen($content)) {
                    $snippet .= '…';
                }

                return $snippet;
            }
        }

        return mb_substr($content, 0, $radius * 2).(mb_strlen($content) > $radius * 2 ? '…' : '');
    }
}

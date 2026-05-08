<?php

declare(strict_types=1);

namespace Aurora\Core\Search\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Search\Service\SearchSnippetBuilder;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Repository\ProjectRepository;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/search', name: 'backend_search')]
#[IsGranted('core.search.view')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly TaxonomyTermRepository $termRepository,
        private readonly MediaRepository $mediaRepository,
        private readonly SearchSnippetBuilder $snippetBuilder,
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectTaskRepository $taskRepository,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = mb_trim((string) $request->query->get('q', ''));
        if ('' === $query) {
            return $this->json(['success' => true, 'posts' => [], 'terms' => [], 'media' => [], 'projects' => [], 'tasks' => []]);
        }

        $defaultLocaleEnum = (string) ($this->getParameter('kernel.default_locale') ?? 'fr');

        // ── Posts (full-text via tsvector) ──────────────────────────────────
        $postIds = $this->postRepository->fullTextPostIds($query, 10);
        $posts = [] !== $postIds ? $this->postRepository->findByIds($postIds) : [];
        $orderById = array_flip($postIds);
        usort($posts, static fn (Post $a, Post $b): int => ($orderById[$a->getId()] ?? PHP_INT_MAX) <=> ($orderById[$b->getId()] ?? PHP_INT_MAX));

        $postsSerialized = array_map(
            fn (Post $post): array => [
                'id' => $post->getId(),
                'title' => $post->getTranslation('fr')?->getTitle() ?? ($post->getTranslations()->first() ?: null)?->getTitle(),
                'status' => $post->getStatus()->value,
                'postType' => $post->getPostType()->getLabel(),
                'trashed' => $post->isTrashed(),
                'snippet' => $this->snippetBuilder->build(
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
                'name' => $term->getTranslation($defaultLocaleEnum)?->getName()
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

        // ── Projects ────────────────────────────────────────────────────────
        $projectsSerialized = array_map(
            static fn (Project $project): array => [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'status' => $project->getStatus()->value,
                'reference' => $project->getReference(),
            ],
            $this->projectRepository->searchByTitle($query),
        );

        // ── Tasks ────────────────────────────────────────────────────────────
        $tasksSerialized = array_map(
            static fn (ProjectTask $task): array => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'reference' => $task->getReference(),
                'projectId' => $task->getProject()->getId(),
                'projectTitle' => $task->getProject()->getTitle(),
            ],
            $this->taskRepository->searchByTitle($query),
        );

        return $this->json([
            'success' => true,
            'posts' => $postsSerialized,
            'terms' => $termsSerialized,
            'media' => $mediaSerialized,
            'projects' => $projectsSerialized,
            'tasks' => $tasksSerialized,
        ]);
    }
}

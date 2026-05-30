<?php

declare(strict_types=1);

namespace Aurora\Module\General\Search\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Aurora\Module\General\Search\Service\SearchResultSorter;
use Aurora\Module\General\Search\Service\SearchSnippetBuilder;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Repository\DocumentRepository;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Repository\ProjectRepository;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/general/search', name: 'backend_general_search')]
#[IsGranted('general.search.view')]
class SearchController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly TaxonomyTermRepository $termRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly SearchSnippetBuilder $snippetBuilder,
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectTaskRepository $taskRepository,
        private readonly SearchResultSorter $searchResultSorter,
        private readonly LocaleContextInterface $localeContext,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = mb_trim((string) $request->query->get('q', ''));
        if ('' === $query) {
            return $this->jsonSuccess(['posts' => [], 'terms' => [], 'media' => [], 'projects' => [], 'tasks' => []]);
        }

        $defaultLocale = $this->localeContext->getDefaultLocale();

        // ── Posts (full-text via tsvector) ──────────────────────────────────
        $postIds = $this->postRepository->fullTextPostIds($query, 10);
        $posts = [] !== $postIds ? $this->searchResultSorter->sortByRelevance($this->postRepository->findByIds($postIds), $postIds) : [];

        $postsSerialized = array_map(
            fn (PostInterface $post): array => [
                'id' => $post->getId(),
                'title' => $post->getTranslation($defaultLocale)?->getTitle() ?? ($post->getTranslations()->first() ?: null)?->getTitle(),
                'status' => $post->getStatus()->value,
                'postType' => $post->getPostType()->getLabel(),
                'trashed' => $post->isTrashed(),
                'snippet' => $this->snippetBuilder->build(
                    $post->getTranslation($defaultLocale)?->getSearchContent()
                    ?? ($post->getTranslations()->first() ?: null)?->getSearchContent(),
                    $query,
                ),
            ],
            $posts,
        );

        // ── Terms ───────────────────────────────────────────────────────────
        $termsSerialized = array_map(
            fn (TaxonomyTermInterface $term): array => [
                'id' => $term->getId(),
                'name' => $term->getTranslation($defaultLocale)?->getName()
                    ?? ($term->getTranslations()->first() ?: null)?->getName(),
                'taxonomy' => $term->getTaxonomy()->getSlug(),
            ],
            $this->termRepository->searchByName($query, 10),
        );

        // ── Media ───────────────────────────────────────────────────────────
        $mediaSerialized = array_map(
            static fn (DocumentInterface $document): array => [
                'id' => $document->getId(),
                'name' => $document->getOriginalName() ?? $document->getTitle(),
                'mimeType' => $document->getMimeType(),
                'alt' => $document->getAlt(),
            ],
            $this->documentRepository->searchByName($query, 10),
        );

        // ── Projects ────────────────────────────────────────────────────────
        $projectsSerialized = array_map(
            static fn (ProjectInterface $project): array => [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'status' => $project->getStatus()->value,
                'reference' => $project->getReference(),
            ],
            $this->projectRepository->searchByTitle($query),
        );

        // ── Tasks ────────────────────────────────────────────────────────────
        $tasksSerialized = array_map(
            static fn (ProjectTaskInterface $task): array => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'reference' => $task->getReference(),
                'projectId' => $task->getProject()->getId(),
                'projectTitle' => $task->getProject()->getTitle(),
            ],
            $this->taskRepository->searchByTitle($query),
        );

        return $this->jsonSuccess([
            'posts' => $postsSerialized,
            'terms' => $termsSerialized,
            'media' => $mediaSerialized,
            'projects' => $projectsSerialized,
            'tasks' => $tasksSerialized,
        ]);
    }
}
